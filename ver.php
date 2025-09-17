<?php
$apiToken = "eyJhbGciOiJIUzI1NiJ9.eyJ0aWQiOjU1ODYyODI1MCwiYWFpIjoxMSwidWlkIjo4MDQ4NDkwNiwiaWFkIjoiMjAyNS0wOS0wNVQxMzo1MDoyNC4wMDBaIiwicGVyIjoibWU6d3JpdGUiLCJhY3RpZCI6MzA5NjU4OTYsInJnbiI6ImV1YzEifQ.rqT6eu5Hu5qn5Jt9hPwiQCYWe1x-rzzIlxbKLbSCHC4";
$boardId = 2112141202;

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
curl_close($ch);

$data = json_decode($response, true);
echo "<pre>";
print_r($data);
echo "</pre>";
