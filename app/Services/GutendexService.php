<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Book;
use App\Models\Author;
use Illuminate\Support\Facades\DB;

class GutendexService
{
    protected string $baseUrl = 'https://gutendex.com';

    /**
     * Lấy danh sách sách từ Gutendex API
     */
    public function getBooks(?string $search = null, int $page = 1)
    {
        $response = Http::get("{$this->baseUrl}/books", [
            'search' => $search,
            'page' => $page
        ]);

        if ($response->successful()) {
            return [
                'status' => 200,
                'data' => $response->json()
            ];
        }

        return [
            'status' => $response->status(),
            'error' => 'Failed to fetch books from Gutendex'
        ];
    }

    /**
     * Lấy chi tiết một cuốn sách từ Gutendex API
     */
    public function getBook(int $id)
    {
        $response = Http::get("{$this->baseUrl}/books/{$id}");

        if ($response->successful()) {
            return [
                'status' => 200,
                'data' => $response->json()
            ];
        }

        return [
            'status' => $response->status(),
            'error' => 'Book not found'
        ];
    }

    /**
     * Lưu sách từ Gutendex vào database local
     */
    public function saveBook(int $bookId)
    {
        $bookData = $this->getBook($bookId);
        
        if (isset($bookData['error'])) {
            return $bookData;
        }

        try {
            DB::beginTransaction();

            // Lấy dữ liệu từ response
            $bookDetails = $bookData['data'];

            // Kiểm tra xem sách đã tồn tại chưa
            $existingBook = Book::where('gutendex_id', $bookId)->first();
            if ($existingBook) {
                return [
                    'status' => 409,
                    'error' => 'Book already exists in database',
                    'data' => $existingBook->load('authors')
                ];
            }

            // Lưu thông tin sách
            $book = Book::create([
                'gutendex_id' => $bookId,
                'title' => $bookDetails['title'],
                'subjects' => $bookDetails['subjects'] ?? [],
                'bookshelves' => $bookDetails['bookshelves'] ?? [],
                'languages' => $bookDetails['languages'] ?? [],
                'summaries' => $bookDetails['summaries'] ?? [],
                'translators' => $bookDetails['translators'] ?? [],
                'copyright' => $bookDetails['copyright'] ?? false,
                'media_type' => $bookDetails['media_type'] ?? 'text',
                'formats' => $bookDetails['formats'] ?? [],
                'download_count' => $bookDetails['download_count'] ?? 0
            ]);

            // Lưu thông tin tác giả
            foreach ($bookDetails['authors'] as $authorData) {
                $author = Author::firstOrCreate(
                    ['gutendex_id' => $authorData['id']],
                    [
                        'name' => $authorData['name'],
                        'birth_year' => $authorData['birth_year'],
                        'death_year' => $authorData['death_year']
                    ]
                );

                $book->authors()->attach($author->id);
            }

            DB::commit();

            return [
                'status' => 200,
                'message' => 'Book saved successfully',
                'data' => $book->load('authors')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 500,
                'error' => 'Failed to save book: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xóa sách khỏi database local
     */
    public function deleteBook(int $id)
    {
        try {
            $book = Book::where('gutendex_id', $id)->firstOrFail();
            $book->authors()->detach();
            $book->delete();

            return [
                'status' => 200,
                'message' => 'Book deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 404,
                'error' => 'Book not found: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy danh sách tác giả từ Gutendex API
     */
    public function getAuthors(?string $search = null, int $page = 1)
    {
        // Gutendex không có API riêng cho tác giả, nên chúng ta lấy sách và trích xuất tác giả
        $response = Http::get("{$this->baseUrl}/books", [
            'search' => $search,
            'page' => $page
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $uniqueAuthors = [];
            
            // Trích xuất tác giả từ sách
            foreach ($data['results'] as $book) {
                foreach ($book['authors'] as $author) {
                    $authorId = $author['id'] ?? md5($author['name']); // Fallback nếu không có ID
                    if (!isset($uniqueAuthors[$authorId])) {
                        $uniqueAuthors[$authorId] = $author;
                    }
                }
            }
            
            return [
                'status' => 200,
                'data' => array_values($uniqueAuthors)
            ];
        }

        return [
            'status' => $response->status(),
            'error' => 'Failed to fetch authors from Gutendex'
        ];
    }

    /**
     * Lấy danh sách sách theo tác giả từ Gutendex API
     */
    public function getBooksByAuthor(int $authorId)
    {
        $response = Http::get("{$this->baseUrl}/books", [
            'author_id' => $authorId
        ]);

        if ($response->successful()) {
            return [
                'status' => 200,
                'data' => $response->json()
            ];
        }

        return [
            'status' => $response->status(),
            'error' => 'Failed to fetch books by author'
        ];
    }
} 