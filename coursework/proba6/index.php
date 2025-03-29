<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protein Analysis Suite</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/ngl@2.0.0-dev.38/dist/ngl.js"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
</head>
<body class="dark-mode">
    <!-- Cookie Consent Modal -->
    <div id="cookieConsent" class="cookie-consent">
        <div class="cookie-overlay"></div>
        <div class="cookie-content glass">
            <span class="cookie-icon">🍪</span>
            <h3>We use cookies!</h3>
            <p>This website uses cookies to remember your previous protein analyses and make your experience smoother.</p>
            <p>By continuing, you agree to our use of cookies. Do you consent?</p>
            <div class="cookie-buttons">
                <button id="acceptCookies" class="cookie-btn accept-btn">Accept Cookies</button>
                <button id="rejectCookies" class="cookie-btn reject-btn">Reject</button>
            </div>
        </div>
    </div>

    <!-- Dark Mode Toggle -->
    <button id="darkModeToggle" class="dark-mode-toggle">
        <span class="toggle-icon"></span>
    </button>

    <!-- Animated Background -->
    <div id="particles-js"></div>

    <!-- Top Navigation Bar -->
    <nav class="top-bar glass">
        <div class="logo-nav-container">
            <div class="logo-tab">
                <img src="images/full_logo.png" alt="Protein Analysis Suite" class="logo">
                <span>Protein Analysis Suite</span>
            </div>

            <div class="nav-links">
                <a href="#" class="nav-link active">Home</a>
                <a href="aves.php" class="nav-link">Example Dataset</a>
                <a href="revisit.php" class="nav-link">Past Jobs</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="form-container glass">
            <h1>Protein Sequence Analysis</h1>

            <form action="process.php" method="post" class="analysis-form">
                <div class="form-group">
                    <label for="protein_family">Protein Family:</label>
                    <input type="text" id="protein_family" name="protein_family" required disabled>
                </div>

                <div class="form-group">
                    <label for="taxonomic_group">Taxonomic Group:</label>
                    <input type="text" id="taxonomic_group" name="taxonomic_group" required disabled>
                </div>

                <button type="submit" class="submit-btn" disabled>Analyze</button>
            </form>

            <!-- Analysis Tools Section -->
            <div class="tools-section">
                <h2>Bioinformatics Analysis Tools</h2>
                <p class="tools-description">Our platform provides comprehensive computational tools for protein characterization:</p>

                <div class="analysis-cards">
                    <div class="card">
                        <div class="card-front">
                            <h3>Conservation Analysis</h3>
                        </div>
                        <div class="card-back">
                            <p>Identifies evolutionarily conserved protein regions using Shannon entropy metrics and multiple sequence alignment.</p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-front">
                            <h3>Motif Scan</h3>
                        </div>
                        <div class="card-back">
                            <p>Detects functional protein motifs using PROSITE patterns and regular expressions.</p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-front">
                            <h3>Content Analysis</h3>
                        </div>
                        <div class="card-back">
                            <p>Quantifies amino acid composition to reveal structural and functional protein properties.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="viewer-container glass">
            <div class="viewer-header">
                <h2>Protein Structure Visualization</h2>
                <p class="viewer-description">Tubulin Tyrosine Ligase (PDB: 6NNG | Chain F) - This enzyme catalyzes the addition of a tyrosine residue to the C-terminus of α-tubulin. The structure shown is from our example dataset ABC Transporters from Avens.</p>
            </div>
            <div id="protein-viewer"></div>
            <div class="reference">
                <p>Kumar, G., Wang, Y., Li, W., & White, S. W. (2019). Tubulin-RB3_SLD-TTL in complex with compound DJ95 [Protein Data Bank entry 6NNG]. RCSB Protein Data Bank. https://doi.org/10.2210/pdb6nng/pdb<br>
                (Original work published July 10, 2019)</p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer glass">
        <div class="footer-content">
            <p>This website was developed as part of the postgraduate course: Introduction to Website and Database Design @ the University of Edinburgh</p>
            <a href="https://github.com/a-ziaj/WDD" target="_blank" class="github-link">
                <i class="fab fa-github"></i> View the source code on GitHub
            </a>
        </div>
    </footer>

    <script>
        // Cookie Consent Functionality
        const cookieConsent = document.getElementById('cookieConsent');
        const acceptCookies = document.getElementById('acceptCookies');
        const rejectCookies = document.getElementById('rejectCookies');
        const formInputs = document.querySelectorAll('input, button[type="submit"]');
        
        // Check if cookies are accepted
        if (!localStorage.getItem('cookiesAccepted')) {
            document.getElementById('cookieConsent').style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        } else {
            enableSiteFeatures();
        }

        // Handle cookie acceptance
        acceptCookies.addEventListener('click', function() {
            localStorage.setItem('cookiesAccepted', 'true');
            hideCookieConsent();
            enableSiteFeatures();
        });

        // Handle cookie rejection
        rejectCookies.addEventListener('click', function() {
            localStorage.setItem('cookiesRejected', 'true');
            hideCookieConsent();
            // You might want to handle rejection differently
            enableSiteFeatures(); // Still enable features but don't set cookies
        });

        function hideCookieConsent() {
            document.getElementById('cookieConsent').style.display = 'none';
            document.body.style.overflow = 'auto'; // Re-enable scrolling
        }

        function enableSiteFeatures() {
            formInputs.forEach(input => {
                input.disabled = false;
            });
        }

        // Initialize NGL Viewer with 6NNG structure
        const body = document.body;
        const stage = new NGL.Stage("protein-viewer", { 
            backgroundColor: body.classList.contains('dark-mode') ? "black" : "white" 
        });
        let component;

        stage.loadFile("https://files.rcsb.org/download/6NNG.pdb").then(function (comp) {
            component = comp;
            // Show only Chain F
            const selection = new NGL.Selection(":F");
            component.addRepresentation("cartoon", {
                color: "residueindex",
                sele: selection.string
            });
            component.autoView();
            component.setSpin(true);
        });

        // Initialize particles.js background
        particlesJS("particles-js", {
            particles: {
                number: { value: 80, density: { enable: true, value_area: 800 } },
                color: { value: "#8d9db6" },
                shape: { type: "circle" },
                opacity: { value: 0.5, random: true },
                size: { value: 3, random: true },
                line_linked: { enable: true, distance: 150, color: "#8d9db6", opacity: 0.2, width: 1 },
                move: { enable: true, speed: 2, direction: "none", random: true, straight: false, out_mode: "out" }
            },
            interactivity: {
                detect_on: "canvas",
                events: {
                    onhover: { enable: true, mode: "grab" },
                    onclick: { enable: true, mode: "push" }
                }
            }
        });

        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');

        // Set dark mode as default
        localStorage.setItem('darkMode', 'enabled');

        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            
            // Update NGL viewer background
            stage.setParameters({
                backgroundColor: body.classList.contains('dark-mode') ? "black" : "white"
            });

            // Save user preference
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'enabled');
            } else {
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    </script>
</body>
</html>
