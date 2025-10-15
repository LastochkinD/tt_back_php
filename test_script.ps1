$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJ0YXNrLXRyYWNrZXItYmFja2VuZCIsImF1ZCI6InRhc2stdHJhY2tlci1mcm9udGVuZCIsImlhdCI6MTc2MDUwNDE0NCwiZXhwIjoxNzYwNTkwNTQ0LCJ1c2VyX2lkIjo3LCJlbWFpbCI6InRlc3RAZXhhbXBsZS5jb20xIn0.seIvqSydpm78r3ETaR7qTilmwvywMHa97W-1ZKqAtvA"

$url = "http://localhost:8080/api/lists?board_id=6"

$headers = @{
    "Authorization" = "Bearer $token"
}

try {
    $response = Invoke-WebRequest -Uri $url -Method GET -Headers $headers

    Write-Host "Status Code: $($response.StatusCode)"
    Write-Host "Response: $($response.Content)"
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}
