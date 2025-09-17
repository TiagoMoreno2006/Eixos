<?php
session_start();
require_once "../script/dbcon.php"; // ligação à BD

if (!isset($_SESSION['formulario'])) {
    header("Location: ../erro.php");
    exit;
}

// Redirect if etapa é diferente de 1
if (isset($_SESSION['formulario']['etapa']) && $_SESSION['formulario']['etapa'] != 1) {
    header("Location: ../main.php");
    exit;
}

$firstName = $_SESSION['utilizador']['first_name'];
$lastName  = $_SESSION['utilizador']['last_name'];
$email     = $_SESSION['utilizador']['email'] ?? 'noemail';
$userId    = $_SESSION['utilizador']['id'] ?? 'nouser';
$etapa     = $_SESSION['formulario']['etapa'] ?? null;

function cleanName($string) {
    return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $string);
}

$clientFolderName = cleanName($userId . '_' . $firstName . '_' . $lastName);
$baseDir = __DIR__ . '/data';
$clientDir = $baseDir . '/' . $clientFolderName;

if (is_dir($clientDir)) {
    function rrmdir($dir) {
        foreach(glob($dir.'/*') as $file) {
            is_dir($file) ? rrmdir($file) : unlink($file);
        }
        rmdir($dir);
    }
    rrmdir($clientDir);
}

// Handle AJAX upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step'])) {
    header('Content-Type: application/json');

    if (!is_dir($clientDir)) mkdir($clientDir, 0777, true);

    // Guardar info empresa e mudança de contabilista
    if ($_POST['step'] == 2) {
        $_SESSION['formulario']['empresa'] = $_POST['empresa'] ?? '';
        $_SESSION['formulario']['nome_empresa'] = $_POST['nome_empresa'] ?? '';
        $_SESSION['formulario']['mudanca'] = $_POST['mudanca'] ?? '';
    }

    $questions = [
        'cc_socios' => 'Cartao_Cidadao_Socios',
        'certidao_permanente' => 'Certidao_Permanente',
        'pacto_social' => 'Pacto_Social',
        'rcbe' => 'RCBE',
        'iban' => 'Comprovativo_IBAN'
    ];

    $allowedTypes = ['application/pdf','image/jpeg','image/png','image/gif'];
    $savedFiles = [];

    foreach ($questions as $inputName => $folderName) {
        if (!isset($_FILES[$inputName])) continue;

        $questionDir = $clientDir . '/' . $folderName;
        if (!is_dir($questionDir)) mkdir($questionDir, 0777, true);

        foreach ($_FILES[$inputName]['tmp_name'] as $i => $tmpPath) {
            if (!is_uploaded_file($tmpPath)) continue;

            $mimeType = mime_content_type($tmpPath);
            if (!in_array($mimeType, $allowedTypes)) {
                echo json_encode(['success'=>false,'message'=>"Arquivo inválido: apenas PDF ou imagens."]);
                exit;
            }

            $originalName = cleanName($_FILES[$inputName]['name'][$i]);
            $destPath = $questionDir . '/' . $originalName;

            if (!move_uploaded_file($tmpPath, $destPath)) {
                echo json_encode(['success'=>false,'message'=>"Erro ao salvar $originalName."]);
                exit;
            }

            $savedFiles[] = $destPath;
        }
    }

    // Final submission
    if ($_POST['step'] == 7) {
        $_SESSION['formulario']['etapa'] = 2;
        $conn = con();
        $stmt = $conn->prepare("UPDATE formulario SET etapa = ? WHERE utilizadores_id = ?");
        $novaEtapa = 2;
        $stmt->bind_param("ii", $novaEtapa, $userId);
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
<title>Formulário Multi-step</title>
<style>
/* --- Perfil do usuário --- */
.user-profile {
    position: fixed;
    top: 15px;
    right: 15px;
    background: #fff;
    padding: 6px 12px;
    border-radius: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    font-size: 13px;
    font-weight: 500;
    z-index: 1000;
}
.user-profile img { width: 28px; height: 28px; border-radius: 50%; margin-right: 6px; }

/* --- Body & icon --- */
body {
    font-family: 'Noto Sans', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    height: 100vh;
    margin: 0;
    background-image: url("../../img/wallpaper.png");
    background-size: cover;
    background-position: center;
}
.icon { width: 140px; margin-bottom: 15px; }

/* --- Container --- */
.container {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
    position: relative;
    text-align: left;
    padding: 24px;
    overflow-y: auto;
    transition: all 0.3s ease;
}
.container.large { width: 60vw; max-width: 700px; height: 85vh; }
.container.small { width: 50vw; max-width: 500px; height: auto; }

/* --- Progress bar --- */
.progress-bar { display: flex; justify-content: space-between; align-items: center; margin: 18px 0; position: relative; }
.progress-bar::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 20px;
    right: 20px;
    height: 3px;
    background: #eee;
    transform: translateY(-50%);
    z-index: 0;
}
.step-dot { width: 18px; height: 18px; background: #ddd; border-radius: 50%; z-index: 1; position: relative; transition: background 0.3s ease, transform 0.2s ease; }
.step-dot.active { background: #ED6102; transform: scale(1.05); }

/* --- Slider steps --- */
.slider-form { display: none; flex-direction: column; font-size: 14px; gap: 14px; }
.slider-form.active { display: flex; }
.slider-form > label:first-child {
    font-size: 16px; font-weight: 700; margin-bottom: 8px; color: #ED6102; border-bottom: 1px solid #eee; padding-bottom: 4px;
}
.slider-form label { display: block; font-weight: 600; font-size: 14px; color: #222; margin-bottom: 4px; }

/* Inputs */
input[type="text"], input[type="file"], select { padding: 8px 10px; font-size: 14px; border-radius: 6px; border: 1px solid #ccc; width: 100%; box-sizing: border-box; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
input[type="file"] { border: none; }
input[type="text"]:focus, input[type="file"]:focus, select:focus { border-color: #ED6102; outline: none; box-shadow: 0 0 4px rgba(237,97,2,0.3); }

/* Buttons */
.button-row { display: flex; justify-content: flex-end; margin-top: 12px; gap: 10px; }
button { padding: 8px 14px; font-size: 14px; border: none; border-radius: 6px; background: #ED6102; color: white; cursor: pointer; min-width: 100px; transition: all 0.2s ease; }
button:hover:not(:disabled) { background: #d95300; }
button:disabled { background: #bbb; color: #666; cursor: not-allowed; }

/* --- Alert box --- */
.alert-box {
    position: relative;
    background: #fff8e6;
    color: #333;
    padding: 10px 14px;
    border-left: 4px solid #ffb400;
    border-radius: 6px;
    font-size: 12px;
    line-height: 1.4;
    margin-top: 20px;
}

/* --- Responsive --- */
@media screen and (max-width:768px){
    .container.large, .container.small { width: 90%; height: auto; padding: 16px; }
    .icon { width: 100px; }
    .button-row { flex-direction: column-reverse; align-items: stretch; }
    button { width: 100%; }
}
</style>
</head>
<body>

<div class="user-profile">
    <img src="../../img/pfp.png" alt="Perfil">
    <span><?= htmlspecialchars($firstName . ' ' . $lastName) ?></span>
</div>

<div class="container large">
    <img class="icon" src="https://eixos.pt/wp-content/uploads/2025/03/EIXOS_Fino_LINHA_i-removebg-preview-e1741613864870.png" alt="Logo"><br><br>
    <div class="progress-bar" id="progressBar"></div><br>

    <form id="multiStepForm" enctype="multipart/form-data">
        <?php
        $steps = [
            '1'=>'Aviso inicial',
            '2'=>'Informações adicionais',
            '3'=>'Cartão de Cidadão dos sócios',
            '4'=>'Certidão da Conservatória',
            '5'=>'Pacto Social',
            '6'=>'RCBE',
            '7'=>'Comprovativo do IBAN'
        ];
        $fileInputs = [
            3=>'cc_socios[]',
            4=>'certidao_permanente[]',
            5=>'pacto_social[]',
            6=>'rcbe[]',
            7=>'iban[]'
        ];
        foreach($steps as $num=>$label){
            echo '<div class="slider-form'.($num==1?' active':'').'" data-step="'.$num.'">';
            echo '<label>'.$num.'. '.$label.'</label><br>';
            if($num==1){
                echo '<div style="text-align:left;font-size:14px;line-height:1.6;">';
                echo '<p><b>Bem-vindo(a) à Eixos!</b></p>';
                echo '<p>Está a receber este formulário porque constituiu uma empresa ou está a mudar de prestador de serviços de contabilidade.<br>
                      Para garantirmos o cumprimento legal e iniciarmos a sua contabilidade com total rigor, necessitamos dos seguintes documentos:</p>';
                echo '<ul>
                        <li>Cartão de Cidadão dos sócios</li>
                        <li>Certidão da Conservatória</li>
                        <li>Pacto Social</li>
                        <li>Registo Central do Beneficiário Efetivo (RCBE)</li>
                        <li>Comprovativo de IBAN</li>
                      </ul>';
                echo '<p><b>Próximos passos:</b><br>
                      Submeta estes documentos através do formulário.<br>
                      Assim que os recebermos, validaremos a informação.<br>
                      Entraremos em contacto consigo para confirmar que tudo está organizado e dar seguimento ao processo.<br>
                      Obrigada pela confiança — na Eixos, a contabilidade é clara e as decisões seguras!</p>';
                echo '</div>';
            } elseif($num==2){
                echo '<label>É uma empresa?</label><br>
                      <select name="empresa" required>
                        <option value="">Selecione</option>
                        <option value="sim">Sim</option>
                        <option value="nao">Não</option>
                      </select><br><br>
                      <div id="empresaNomeField" style="display:none;">
                        <label>Nome da empresa:</label><br>
                        <input type="text" name="nome_empresa" placeholder="Digite o nome da empresa"><br><br>
                      </div>
                      <label>Está a mudar de prestador de contabilidade?</label><br>
                      <select name="mudanca" required>
                        <option value="">Selecione</option>
                        <option value="sim">Sim</option>
                        <option value="nao">Não</option>
                      </select><br><br>';
            } elseif(isset($fileInputs[$num])){
                echo '<input type="file" name="'.$fileInputs[$num].'" accept=".pdf,image/*" required>';
            }
            echo '<div class="button-row">';
            if($num>1) echo '<button type="button" class="prev">Anterior</button>';
            if($num<count($steps)) echo '<button type="button" class="next" disabled>Próximo</button>';
            else echo '<button type="submit" disabled>Submeter</button>';
            echo '</div></div>';
        }
        ?>

        <!-- ALERT BOX MOVED TO END -->
        <div class="alert-box" id="alertBox">
            <span>⚠️ Atenção: <br> Caso a página seja fechada ou reiniciada o seu progresso será perdido.</span>
        </div>

    </form>
</div>

<script>
const totalSteps = <?=count($steps)?>;
const progressBar = document.getElementById('progressBar');
const form = document.getElementById('multiStepForm');
let currentStep = 1;

function initProgressBar(){
    progressBar.innerHTML='';
    for(let i=1;i<=totalSteps;i++){
        const dot=document.createElement('div');
        dot.classList.add('step-dot');
        progressBar.appendChild(dot);
    }
}
initProgressBar();

function updateDots(step){
    document.querySelectorAll('.step-dot').forEach((dot,i)=>dot.classList.toggle('active', i<step));
}

function updateContainerSize(step){
    const container = document.querySelector('.container');
    if(step === 1 || step === 2){
        container.classList.add('large');
        container.classList.remove('small');
    } else {
        container.classList.add('small');
        container.classList.remove('large');
    }
}

function showStep(step){
    document.querySelectorAll('.slider-form').forEach(f=>f.classList.remove('active'));
    document.querySelector(`.slider-form[data-step="${step}"]`).classList.add('active');
    updateDots(step);
    validateStep(step);
    updateContainerSize(step);
}

function validateStep(step) {
    const stepEl = document.querySelector(`.slider-form[data-step="${step}"]`);
    const nextBtn = stepEl.querySelector(".next");
    const submitBtn = stepEl.querySelector("[type=submit]");
    let valid = true;
    const inputs = stepEl.querySelectorAll("[required]");
    for (let inp of inputs) {
        if ((inp.type === "file" && inp.files.length === 0) || !inp.value) {
            valid = false;
            break;
        }
    }
    if (nextBtn) nextBtn.disabled = !valid;
    if (submitBtn) submitBtn.disabled = !valid;
}

document.addEventListener("input", () => validateStep(currentStep));
document.addEventListener("change", (e) => {
    if (e.target.name === "empresa") {
        document.getElementById("empresaNomeField").style.display = e.target.value === "sim" ? "block" : "none";
        document.querySelector("[name='nome_empresa']").required = e.target.value === "sim";
    }
    validateStep(currentStep);
});

function uploadStep(stepEl, onSuccess){
    const fd=new FormData(form);
    fd.append('step', stepEl.dataset.step);
    fetch("", {method:"POST", body:fd})
    .then(r=>r.json())
    .then(data=>{
        if(data.success){
            if (data.redirect) window.location.href = data.redirect;
            else onSuccess();
        } else {
            alert("Erro: "+data.message);
        }
    }).catch(err=>{
        alert("Erro técnico ao enviar ficheiro.");
        console.error(err);
    });
}

document.querySelectorAll('.next').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const stepEl=document.querySelector(`.slider-form[data-step="${currentStep}"]`);
        uploadStep(stepEl, ()=>{if(currentStep<totalSteps){currentStep++; showStep(currentStep);}});
    });
});

document.querySelectorAll('.prev').forEach(btn=>{
    btn.addEventListener('click', ()=>{if(currentStep>1){currentStep--; showStep(currentStep);}});
});

form.addEventListener('submit', e=>{
    e.preventDefault();
    const stepEl=document.querySelector(`.slider-form[data-step="${currentStep}"]`);
    uploadStep(stepEl, ()=>{});
});

showStep(currentStep);
</script>

</body>
</html>
