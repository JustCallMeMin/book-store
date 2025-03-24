<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GutendexService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TestGutendexImport extends Command
{
    protected $signature = 'test:gutendex-import {book_id=84 : ID of the book to import}';
    protected $description = 'Test importing a single book from Gutendex API with detailed error tracing';

    public function handle(GutendexService $gutendexService)
    {
        $bookId = 1342; // Pride and Prejudice
        $this->info("Testing import of book ID: " . $bookId);
        
        // Enable query logging
        DB::enableQueryLog();
        
        try {
            $this->info("Fetching book information from Gutendex API...");
            $bookData = $gutendexService->getBook($bookId);
            
            if (isset($bookData['error'])) {
                $this->error("API Error: " . ($bookData['error'] ?? 'Unknown error'));
                return 1;
            }
            
            $this->info("Book information retrieved successfully:");
            $this->info("Title: " . $bookData['data']['title']);
            $this->info("Authors: " . implode(", ", array_column($bookData['data']['authors'] ?? [], 'name')));
            
            // Try saving the book with detailed error reporting
            $this->info("Attempting to save book to database...");
            
            $importResult = $gutendexService->saveBook($bookId);
            
            if ($importResult['status'] === 200) {
                $this->info("Book imported successfully!");
                $this->info("Book ID in database: " . $importResult['data']->id);
                
                // Show the authors that were created/attached
                $this->info("Authors: " . implode(", ", $importResult['data']->authors->pluck('name')->toArray()));
                
                // Show the categories that were created/attached
                $this->info("Categories: " . implode(", ", $importResult['data']->categories->pluck('name')->toArray()));
                
                return 0;
            } else {
                $this->error("Failed to import book: " . ($importResult['error'] ?? 'Unknown error'));
                $this->line("Response: " . json_encode($importResult));
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Exception occurred: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . ":" . $e->getLine());
            $this->error("Stack trace:");
            $this->error($e->getTraceAsString());
            
            // Show all SQL queries that were executed
            $this->info("SQL Queries:");
            foreach (DB::getQueryLog() as $query) {
                $this->line(sqlFormatter($query['query'], $query['bindings']));
            }
            
            return 1;
        }
    }
}

// Helper function to format SQL queries with bindings
function sqlFormatter($query, $bindings)
{
    $sql = $query;
    foreach ($bindings as $i => $binding) {
        if (is_string($binding)) {
            $binding = "'" . addslashes($binding) . "'";
        } elseif (is_bool($binding)) {
            $binding = $binding ? '1' : '0';
        } elseif (is_null($binding)) {
            $binding = 'NULL';
        }
        
        $sql = preg_replace('/\?/', $binding, $sql, 1);
    }
    
    return $sql;
} 