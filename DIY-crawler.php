<?php

// Author: Kris Tomplait
// this script was made by me so use at your own risk

function crawl_and_store($query, $depth) {
    // Execute the Python script with the provided input
    $command = "python3 - <<END
import requests
from bs4 import BeautifulSoup
import sqlite3

def crawl(query, depth):
    # Web crawling logic
    results = []

    # Example: Crawl the DuckDuckGo search results
    search_url = f'https://duckduckgo.com/html?q={query}'
    response = requests.get(search_url)
    
    # Parse the HTML content
    soup = BeautifulSoup(response.text, 'html.parser')
    
    # Extract relevant information
    links = soup.find_all('a', {'class': 'result__url'})
    
    for link in links:
        result_text = link.text.strip()
        results.append(result_text)

    # Store the results in the SQLite database
    conn = sqlite3.connect('web_data.db')
    c = conn.cursor()
    c.execute('CREATE TABLE IF NOT EXISTS web_results (id INTEGER PRIMARY KEY AUTOINCREMENT, result TEXT)')
    for result in results:
        c.execute('INSERT INTO web_results (result) VALUES (?)', (result,))
    conn.commit()
    conn.close()

crawl('$query', $depth)
END";

    $output = shell_exec($command);

    // Display the crawled results
    $db = new SQLite3('web_data.db');
    $results = $db->query("SELECT * FROM web_results");
    echo "<h2>Search Results:</h2>";
    while ($row = $results->fetchArray()) {
        echo "<pre>" . $row['result'] . "</pre>";
    }
    $db->close();
}

// Prompt the user for search query
$query = readline("Enter your search query: ");

// Prompt the user for crawling depth or set a default value
$depth = readline("Enter the crawling depth (default: 5): ");
$depth = intval($depth);  // Convert depth to an integer

// Set the default crawling depth if not provided or invalid
if ($depth <= 0) {
    $depth = 5;  // Default depth
}

// Execute the initial crawl to determine the number of search results
crawl_and_store($query, $depth);

// Read the number of search results from the database
$db = new SQLite3('web_data.db');
$num_results = $db->querySingle("SELECT COUNT(*) FROM web_results");
$db->close();

// Adjust the crawling depth based on the number of results
if ($num_results > 0) {
    $depth = ceil(sqrt($num_results));  // Adjust the depth based on a mathematical formula
}

// Perform the final crawl with the adjusted depth
crawl_and_store($query, $depth);
?>
