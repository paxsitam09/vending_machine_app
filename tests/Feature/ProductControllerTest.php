<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use PDO;
use DB;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run the database migrations
        $this->artisan('migrate');

        // Create an admin user and log in
        $admin = User::factory()->create(['role' => 'admin']);
        Auth::login($admin);
    }

    public function test_it_can_pdo_connection()
    {
        $response = $this->get('/test-product-pdo-connection');

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'PDO connection is working',
                 ]);
    }

    public function test_it_can_list_all_products()
    {
        // Create some products
        $products = [
            ['name' => 'Product 1', 'price' => 10.0, 'quantity' => 5],
            ['name' => 'Product 2', 'price' => 20.0, 'quantity' => 10],
        ];

        foreach ($products as $product) {
            $this->post('/products', $product);
        }

        // List products
        $response = $this->get('/products');

        $response->assertStatus(200);
        $response->assertViewHas('products');
    }

    public function test_it_can_store_a_new_product()
    {
        $productData = [
            'name' => 'New Product',
            'price' => 15.0,
            'quantity' => 20,
        ];

        $response = $this->post('/products', $productData);

        $response->assertStatus(302); // Redirect after storing
        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'price' => 15.0,
            'quantity' => 20,
        ]);
    }

    public function test_it_can_update_an_existing_product()
    {
        // First, create a product
        $productData = [
            'name' => 'Existing Product',
            'price' => 25.0,
            'quantity' => 5,
        ];
        $this->post('/products', $productData);

        $product = DB::table('products')->where('name', 'Existing Product')->first();

        // Update the product
        $updatedData = [
            'name' => 'Updated Product',
            'price' => 30.0,
            'quantity' => 10,
        ];

        $response = $this->put("/products/{$product->id}", $updatedData);

        $response->assertStatus(302); // Redirect after updating
        $this->assertDatabaseHas('products', [
            'name' => 'Updated Product',
            'price' => 30.0,
            'quantity' => 10,
        ]);
    }

    public function test_it_can_delete_a_product()
    {
        // First, create a product
        $productData = [
            'name' => 'Product to Delete',
            'price' => 10.0,
            'quantity' => 1,
        ];
        $this->post('/products', $productData);

        $product = DB::table('products')->where('name', 'Product to Delete')->first();

        // Delete the product
        $response = $this->delete("/products/{$product->id}");

        $response->assertStatus(302); // Redirect after deleting
        $this->assertDatabaseMissing('products', [
            'name' => 'Product to Delete',
        ]);
    }
}
