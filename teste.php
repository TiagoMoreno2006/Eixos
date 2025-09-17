<?php
// ========================
// CONFIGURATION
// ========================
$apiToken = "eyJhbGciOiJIUzI1NiJ9.eyJ0aWQiOjU1ODYyODI1MCwiYWFpIjoxMSwidWlkIjo4MDQ4NDkwNiwiaWFkIjoiMjAyNS0wOS0wNVQxMzo1MDoyNC4wMDBaIiwicGVyIjoibWU6d3JpdGUiLCJhY3RpZCI6MzA5NjU4OTYsInJnbiI6ImV1YzEifQ.rqT6eu5Hu5qn5Jt9hPwiQCYWe1x-rzzIlxbKLbSCHC4";   // Replace with your Monday.com API token
$boardId = 2112141202;               // Replace with your board ID
$groupTitle = "Empresas"; // Human-readable group title
$itemName = "Nova Tarefa";           // The item name you want to create

// Optional: Column values
$columnValues = [
    "status" => ["label" => "Em andamento"],
    "text"   => "Exemplo de nota"
];

// ========================
// FUNCTIONS
// ========================

/**
 * Makes a POST request to Monday.com GraphQL API
 */
function mondayApiRequest($query, $apiToken) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.monday.com/v2/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: $apiToken"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die("cURL Error: " . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

/**
 * Get group_id from board by group title
 */
function getGroupId($boardId, $groupTitle, $apiToken) {
    $query = <<<GRAPHQL
{
  boards(ids: $boardId) {
    groups {
      id
      title
    }
  }
}
GRAPHQL;

    $response = mondayApiRequest($query, $apiToken);

    if (!isset($response['data']['boards'][0]['groups'])) {
        die("Error fetching groups.");
    }

    foreach ($response['data']['boards'][0]['groups'] as $group) {
        if ($group['title'] === $groupTitle) {
            return $group['id'];
        }
    }

    die("Group title '$groupTitle' not found.");
}

// ========================
// MAIN
// ========================

// 1. Get the correct group_id
$groupId = getGroupId($boardId, $groupTitle, $apiToken);

// 2. Prepare column values
$columnValuesJson = json_encode($columnValues);
$columnValuesEscaped = addslashes($columnValuesJson);

// 3. Create the new item
$mutation = <<<GRAPHQL
mutation {
  create_item (
    board_id: $boardId,
    group_id: "$groupId",
    item_name: "$itemName",
    column_values: "$columnValuesEscaped"
  ) {
    id
    name
  }
}
GRAPHQL;

// 4. Execute the mutation
$result = mondayApiRequest($mutation, $apiToken);

// 5. Output result
echo "<pre>";
print_r($result);
echo "</pre>";
?>
