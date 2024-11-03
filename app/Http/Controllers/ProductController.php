<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDO;
use DB;

class ProductController extends Controller
{
    private $pdo;

    public function __construct()
    {
        // Initialize PDO connection
        $this->pdo = DB::connection()->getPdo();
    }
    public function testPdoConnection()
    {
        try {
            // Run a simple query to check the connection
            $stmt = $this->pdo->query('SELECT 1');
            return response()->json(['status' => 'success', 'message' => 'PDO connection is working']);
        } catch (\PDOException $e) {
            return response()->json(['status' => 'error', 'message' => 'PDO connection failed', 'error' => $e->getMessage()]);
        }
    }

    // List all products
    public function index(Request $request)
    {
        $sortBy = $request->get('sort', 'name'); // Default sort by 'name'
        $sortOrder = $request->get('order', 'asc');

        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM products ORDER BY $sortBy $sortOrder LIMIT 10 OFFSET :offset";        $page = $request->get('page', 1);
        $offset = ($page - 1) * 10;

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $totalStmt = $this->pdo->query("SELECT FOUND_ROWS() as total");
        $total = $totalStmt->fetch(PDO::FETCH_OBJ)->total;
        $totalPages = ceil($total / 10);



        return view('products.index', compact('products', 'sortBy', 'sortOrder', 'page', 'totalPages'));
        }
    public function show($id)
    {
        $query = "SELECT * FROM products WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$product) {
            abort(404);
        }

        return view('products.show', compact('product'));
    }

    // Show create product form
    public function create()
    {
        $query = "SELECT role FROM users WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }
        return view('products.create');
    }

    public function createproduct()
    {
        $query = "SELECT role FROM users WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }
        return view('product_management.create');
    }

    // Store a new product
    public function store(Request $request)
    {   
        $validatedData = $request->validate([
            'name' => 'required|string|max:255', // Ensure the name is provided
            'price' => 'required|numeric|min:0.01', // Ensure the price is positive
            'quantity' => 'required|integer|min:0', // Ensure the quantity is non-negative
        ]);
    
        $query = "SELECT role FROM users WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $name = $validatedData['name'];
        $price = $validatedData['price'];
        $quantity = $validatedData['quantity'];

        $stmt = $this->pdo->prepare("INSERT INTO products (name, price, quantity) VALUES (:name, :price, :quantity)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->execute();
        return redirect()->route('products.index');
    }

    // Show edit product form
    public function edit($id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        return view('products.edit', compact('product'));
    }

    // Update a product
    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $name = $request->input('name');
        $price = $request->input('price');
        $quantity = $request->input('quantity');

        $stmt = $this->pdo->prepare("UPDATE products SET name = :name, price = :price, quantity = :quantity WHERE id = :id");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return redirect()->route('products.index');
    }

    // Delete a product
    public function destroy($id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return redirect()->route('products.index');
    }

    // Make a transaction
    public function makeTransaction(Request $request, $productId)    {

        $userId = Auth::id();
        $quantity = $request->input('quantity');
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $productId);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        $price = $product['price'];
        $total = round($quantity * $price, 3);

        if (!$product || $product['quantity'] < $quantity) {
            return redirect()->route('products.index')->with('error', 'Product not available or insufficient quantity.');
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

        return redirect()->route('products.index')->with('success', 'Transaction successful.');
    }


    // View all transactions
    public function viewTransactions()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $stmt = $this->pdo->prepare("SELECT transactions.*, products.name AS product_name, users.name AS user_name FROM transactions 
                                     JOIN products ON transactions.product_id = products.id 
                                     JOIN users ON transactions.user_id = users.id");
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return view('transactions.index', compact('transactions'));
    }
}