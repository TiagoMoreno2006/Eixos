<?php
session_start();
require_once "../script/dbcon.php";

// Only allow if etapa = 2
if (!isset($_SESSION['formulario']['etapa']) || $_SESSION['formulario']['etapa'] != 2) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../main.php");
        exit;
    }
}

$firstName = $_SESSION['utilizador']['first_name'];
$lastName  = $_SESSION['utilizador']['last_name'];
$userId    = $_SESSION['utilizador']['id'] ?? 'nouser';

function cleanName($string) {
    return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $string);
}

// Base path
$baseDir = __DIR__ . '/data';
$userFolder = cleanName($userId . '_' . $firstName . '_' . $lastName);
$extraDir = $baseDir . '/' . $userFolder . '/extra';
if (!is_dir($extraDir)) mkdir($extraDir, 0777, true);

// Clear extra folder before saving new files
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    foreach (glob($dir.'/*') as $file) {
        is_dir($file) ? rrmdir($file) : unlink($file);
    }
}
rrmdir($extraDir);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $steps = [
        1=>'Balancete',
        2=>'SAFT',
        3=>'Balancete_Analitico',
        4=>'Imobilizacao',
        5=>'Funcionarios',
        6=>'Recibos',
        7=>'Programas',
        8=>'CEGID'
    ];

    $allowedTypes = ['application/pdf','image/jpeg','image/png','image/gif'];
    $savedFiles = [];

    foreach ($_FILES as $inputName => $fileInfo) {
        $count = count($fileInfo['name']);
        if ($count < 1) {
            echo json_encode(['success'=>false,'message'=>"É necessário enviar um ficheiro para $inputName."]);
            exit;
        }

        // Short folder name
        $folderLabel = '';
        foreach($steps as $num=>$label){
            $slug = strtolower($label);
            if(str_replace(' ','_',$slug) === $inputName){ $folderLabel = cleanName($label); break; }
        }
        if(!$folderLabel) $folderLabel = 'extra_files';

        $stepFolder = $extraDir . '/' . $folderLabel;
        if (!is_dir($stepFolder)) mkdir($stepFolder, 0777, true);

        // Delete previous file in folder to allow overwrite
        $existingFiles = glob($stepFolder.'/*');
        foreach($existingFiles as $f) unlink($f);

        for ($i = 0; $i < $count; $i++) {
            $tmpPath = $fileInfo['tmp_name'][$i];
            $originalName = $fileInfo['name'][$i];

            if (!is_uploaded_file($tmpPath)) continue;

            $mimeType = mime_content_type($tmpPath);
            if (!in_array($mimeType, $allowedTypes)) {
                echo json_encode(['success'=>false,'message'=>"Arquivo inválido: apenas PDF ou imagens."]);
                exit;
            }

            $cleanFileName = cleanName($originalName);
            $destPath = $stepFolder . '/' . $cleanFileName;
            if (!move_uploaded_file($tmpPath, $destPath)) {
                echo json_encode(['success'=>false,'message'=>"Erro ao salvar $originalName"]);
                exit;
            }
            $savedFiles[] = $destPath;
        }
    }

    // Final step
    if (isset($_POST['step']) && $_POST['step'] == '8') {
        $_SESSION['formulario']['etapa'] = 3;
        $conn = con();
        $stmt = $conn->prepare("UPDATE formulario SET etapa = 3 WHERE utilizadores_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        echo json_encode(['success'=>true,'redirect'=>'../main.php']);
        exit;
    }

    echo json_encode(['success'=>true,'files'=>$savedFiles]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Formulário Extra</title>
<link rel="stylesheet" href="../../css/forms.css">
</head>
<body>

<div class="user-profile">
    <img src="../../img/pfp.png" alt="Perfil">
    <span><?= htmlspecialchars($firstName . ' ' . $lastName) ?></span>
</div>

<div class="alert-box">
⚠️ Atenção: <br> Caso a página seja fechada ou reiniciada o seu progresso será perdido.
</div>

<div class="container">
    <img class="icon" src="https://eixos.pt/wp-content/uploads/2025/03/EIXOS_Fino_LINHA_i-removebg-preview-e1741613864870.png" alt="Logo"><br><br>

<form id="extraForm" enctype="multipart/form-data">
<?php
$steps = [
    1=>'Balancete',
    2=>'SAFT',
    3=>'Balancete Analitico',
    4=>'Imobilizacao',
    5=>'Funcionarios',
    6=>'Recibos de vencimento',
    7=>'Programas',
    8=>'CEGID'
];

foreach($steps as $num=>$label){
    echo '<div class="slider-form'.($num==1?' active':'').'" data-step="'.$num.'">';
    echo '<label>'.$num.'. '.$label.'</label><br>';
    $inputName = strtolower(str_replace([' ','ç','é'],['_','c','e'],$label));
    $multiple = ($num==6) ? '' : '';
    echo '<input type="file" name="'.$inputName.'[]" accept=".pdf,image/*" '.$multiple.' required>';
    echo '<div class="button-row">';
    if($num>1) echo '<button type="button" class="prev">Anterior</button>';
    if($num<count($steps)) echo '<button type="button" class="next" disabled>Próximo</button>';
    else echo '<button type="submit">Submeter</button>';
    echo '</div></div>';
}
?>
</form>
</div>

<script>
let currentStep = 1;
const totalSteps = <?=count($steps)?>;

function showStep(step){
    document.querySelectorAll('.slider-form').forEach(f=>f.classList.remove('active'));
    document.querySelector(`.slider-form[data-step="${step}"]`).classList.add('active');
    validateStep(step);
}

function validateStep(step){
    const stepEl = document.querySelector(`.slider-form[data-step="${step}"]`);
    const nextBtn = stepEl.querySelector('.next');
    if(!nextBtn) return;
    let valid=true;
    const inputs=stepEl.querySelectorAll('[required]');
    for(let inp of inputs){
        if(inp.type==='file' && inp.files.length===0){ valid=false; break; }
        else if(!inp.value){ valid=false; break; }
    }
    nextBtn.disabled=!valid;
}

document.addEventListener('change',()=>validateStep(currentStep));
document.addEventListener('input',()=>validateStep(currentStep));

function uploadStep(stepEl, onSuccess){
    const fd = new FormData(document.getElementById('extraForm'));
    fd.append('step', stepEl.dataset.step);
    fetch("",{method:"POST",body:fd})
    .then(r=>r.json()).then(data=>{
        if(data.success){
            if(data.redirect) window.location.href = data.redirect;
            else onSuccess();
        }else alert('Erro: '+data.message);
    }).catch(err=>{alert('Erro técnico ao enviar ficheiro'); console.error(err);});
}

document.querySelectorAll('.next').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const stepEl=document.querySelector(`.slider-form[data-step="${currentStep}"]`);
        uploadStep(stepEl, ()=>{
            if(currentStep<totalSteps){ currentStep++; showStep(currentStep); }
        });
    });
});

document.querySelectorAll('.prev').forEach(btn=>{
    btn.addEventListener('click', ()=>{ if(currentStep>1){ currentStep--; showStep(currentStep); }});
});

document.getElementById('extraForm').addEventListener('submit', e=>{
    e.preventDefault();
    const stepEl=document.querySelector(`.slider-form[data-step="${currentStep}"]`);
    uploadStep(stepEl, ()=>{});
});

showStep(currentStep);
</script>
</body>
</html>
