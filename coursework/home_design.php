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
    <link rel="icon" href="images/full_logo.png" type="image/png">
    <link rel="stylesheet" href="style3.css">
    <script src="https://cdn.jsdelivr.net/npm/ngl@2.0.0-dev.38/dist/ngl.js"></script>
</head>
<body>

<!-- Navbar with logo and dropdown -->
<div class="navbar">
  <div class="logo">
    <img src="images/full_logo.png" alt="Logo">
  </div>
  
  <div class="separator">|</div>

  <div class="dropdown">
    <button class="dropbtn">Menu</button>
    <div class="dropdown-content">
      <a href="#">Link 1</a>
      <a href="#">Link 2</a>
      <a href="#">Link 3</a>
    </div>
  </div>
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
            component.setSpin(rotating);
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
