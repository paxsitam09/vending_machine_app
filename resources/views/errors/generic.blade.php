<!-- resources/views/errors/custom.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
            color: #333;
        }
        .error-container {
            text-align: center;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 2.5rem;
            color: #e63946;
            margin-bottom: 20px;
        }
        p {
            font-size: 1.2rem;
            color: #555;
            margin-bottom: 30px;
        }
        .home-link {
            display: inline-block;
            padding: 10px 20px;
            background-color: #457b9d;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .home-link:hover {
            background-color: #1d3557;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Error - Something Went Wrong</h1>
        <p>{{ $message }}</p>
        <a href="/" class="home-link">Return to Homepage</a>
    </div>
</body>
</html>
