<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $proteinName = htmlspecialchars($_POST['protein_name'] ?? '');
    $taxonomicGroup = htmlspecialchars($_POST['taxonomic_group'] ?? '');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hello</title>
    <link rel="icon" href="logo.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/ngl@2.0.0-dev.38/dist/ngl.js"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: white;
            color: black;
            line-height: 1.6;
            overflow-x: hidden;
        }

        #top-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem;
            background: rgb(64, 64, 64);
            color: white;
            text-align: left;
            z-index: 1000;
        }

        #top-bar a {
            color: white;
            text-decoration: none;
            margin-right: 2rem;
        }

        .container {
            margin-top: 120px;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            padding: 2rem;
        }

        .left-section {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            width: 45%;
        }

        .right-section {
            width: 50%;
        }

        #protein-viewer {
            width: 100%;
            height: 400px;
            background-color: white;
            border: 1px solid black;
        }

        .panel {
            background: rgb(200, 200, 200);
            padding: 1.5rem;
            border-radius: 12px;
        }

        .description-box {
            background: rgb(200, 200, 200);
            padding: 0.5rem;
            border-radius: 8px;
            display: inline-block;
            margin-top: 1rem;
        }

        label {
            display: block;
            margin-top: 1rem;
        }

        input[type="text"] {
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.5rem;
            border: 1px solid black;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            color: white;
            background: rgb(25, 32, 72);
            border: none;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.3s ease;
            margin-top: 1rem;
        }

        .btn:hover {
            background: rgb(38, 38, 38);
        }

        footer {
            text-align: center;
            padding: 1rem;
            background: rgb(20, 20, 20);
            color: white;
        }

        a.highlight {
            color: red;
            text-decoration: underline;
        }

    </style>
</head>
<body>

<div id="top-bar">
    <a href="test.php">Option 1</a>
    <a href="test.php">Option 2</a>
    <a href="test.php">Option 3</a>
</div>

<div class="container">
    <div class="left-section">
        <div class="panel">
            <h2><strong>Welcome to our service</strong></h2>
            <p>We do all of this cool stuff. <a class="highlight" href="#">Some setnece</a>. <a class="highlight" href="#">Some sentence</a></p>
        </div>

        <div class="panel">
            <form action="" method="POST">
                <label for="protein_name">Enter the protein name:</label>
                <input type="text" id="protein_name" name="protein_name" required>

                <label for="taxonomic_group">Enter the <a class="highlight" href="#">taxonomic group</a>:</label>
                <input type="text" id="taxonomic_group" name="taxonomic_group" required>

                <button type="submit" class="btn">Submit</button>
            </form>
        </div>
    </div>

    <div class="right-section">
        <div id="protein-viewer"></div>
        <div class="description-box">
            <a class="highlight" href="#">some</a> protein <a class="highlight" href="#">description</a>
        </div>
    </div>
</div>

<footer>Proteins are fun</footer>

<script>
    const stage = new NGL.Stage("protein-viewer");
    stage.loadFile("https://files.rcsb.org/download/6XND.pdb", { defaultRepresentation: true }).then(function (component) {
	    component.autoView();
	    stage.setParameters({ backgroundColor: "white" });


	    
        let rotating = true;
        const rotate = () => {
            if (rotating) component.setSpin(true);
            else component.setSpin(false);
        };

        document.getElementById("protein-viewer").addEventListener("click", () => {
            rotating = !rotating;
            rotate();
        });

        rotate();
    });
</script>

</body>
</html>

