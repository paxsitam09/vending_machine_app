<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDO;
use DB;
use App\Http\Controllers\Controller;
use Route;



class VendingMachineController extends Controller
{
    private $pdo;

    public function __construct()
    {
        // Initialize PDO connection
        
        $this->middleware('auth:api');
        $this->pdo = DB::connection()->getPdo();
    }

    
    // List all products (GET /api/products)
    public function index(Request $request)
    {
        $sortBy = $request->get('sort', 'name');
        $sortOrder = $request->get('order', 'asc');
        $page = $request->get('page', 1);
        $offset = ($page - 1) * 10;

        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM products ORDER BY $sortBy $sortOrder LIMIT 10 OFFSET :offset";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $totalStmt = $this->pdo->query("SELECT FOUND_ROWS() as total");
        $total = $totalStmt->fetch(PDO::FETCH_OBJ)->total;
        $totalPages = ceil($total / 10);

        return response()->json([
            'products' => $products,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);
    }

    // Show a single product (GET /api/products/{id})
    public function show($id)
    {
        $query = "SELECT * FROM products WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    // Store a new product (POST /api/products)
    public function store(Request $request)
    {
        // Check if the user is an admin
        $userId = Auth::id();
        $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = :id");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $userRole = $stmt->fetch(PDO::FETCH_OBJ)->role ?? null;

        if ($userRole !== 'admin') {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0.01',
            'quantity' => 'required|integer|min:0',
        ]);

        $name = $validatedData['name'];
        $price = $validatedData['price'];
        $quantity = $validatedData['quantity'];

        $stmt = $this->pdo->prepare("INSERT INTO products (name, price, quantity) VALUES (:name, :price, :quantity)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->execute();

        return response()->json(['message' => 'Product created successfully'], 201);
    }

    // Update a product (PUT /api/products/{id})
    public function update(Request $request, $id)
    {
        // Check if the user is an admin
        $userId = Auth::id();
        $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = :id");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $userRole = $stmt->fetch(PDO::FETCH_OBJ)->role ?? null;

        if ($userRole !== 'admin') {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0.01',
            'quantity' => 'required|integer|min:0',
        ]);

        $stmt = $this->pdo->prepare("UPDATE products SET name = :name, price = :price, quantity = :quantity WHERE id = :id");
        $stmt->bindParam(':name', $validatedData['name']);
        $stmt->bindParam(':price', $validatedData['price']);
        $stmt->bindParam(':quantity', $validatedData['quantity']);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return response()->json(['message' => 'Product updated successfully']);
    }

    // Delete a product (DELETE /api/products/{id})
    public function destroy($id)
    {
        // Check if the user is an admin
        $userId = Auth::id();
        $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = :id");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $userRole = $stmt->fetch(PDO::FETCH_OBJ)->role ?? null;

        if ($userRole !== 'admin') {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    // Make a transaction (POST /api/products/{productId}/transaction)
    public function makeTransaction(Request $request, $productId)
    {
        // Check if the user is an admin
        $userId = Auth::id();
        $quantity = $request->input('quantity');

        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $productId);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        $price = $product['price'];
        $total = round($quantity * $price, 3);


        if (!$product || $product['quantity'] < $quantity) {
            return response()->json(['error' => 'Product not available or insufficient quantity'], 400);
        }

        // Update the quantity
        $newQuantity = $product['quantity'] - $quantity;
        $stmt = $this->pdo->prepare("UPDATE products SET quantity = :quantity WHERE id = :id");
        $stmt->bindParam(':quantity', $newQuantity);
        $stmt->bindParam(':id', $productId);
        $stmt->execute();

        // Log the transaction
        $stmt = $this->pdo->prepare("INSERT INTO transactions (product_id, quantity, user_id, created_at, total) VALUES (:product_id, :quantity, :user_id, NOW(), :total)");
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':total', $total);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return response()->json(['message' => 'Transaction successful']);
    }

    // Method to view transactions and return them as JSON
    public function viewTransactions()
    {
        // Check if the user is an admin
        $userId = Auth::id();
        $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = :id");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $userRole = $stmt->fetch(PDO::FETCH_OBJ)->role ?? null;

        if ($userRole !== 'admin') {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        // Fetch transactions with product and user information
        $stmt = $this->pdo->prepare("SELECT transactions.*, products.name AS product_name, users.name AS user_name 
                                    FROM transactions 
                                    JOIN products ON transactions.product_id = products.id 
                                    JOIN users ON transactions.user_id = users.id");
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return response()->json($transactions);
    }
}
