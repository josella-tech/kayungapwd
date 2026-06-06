<?php require_once 'backend/includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Kayunga District Youth Council</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .team-card-v { background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.08); margin-bottom:25px; display:flex; flex-direction:column; height:100%; }
        .team-card-v:hover { transform:translateY(-5px); box-shadow:0 8px 25px rgba(0,0,0,0.12); }
        .team-card-v .img { width:100%; height:250px; min-height:200px; overflow:hidden; background:#f3f4f6; flex-shrink:0; }
        .team-card-v .img img { width:100%; height:100%; object-fit:cover; }
        .team-card-v .txt { padding:15px; text-align:center; border:2px solid #d97706; border-top:none; border-radius:0 0 12px 12px; background:#fff; flex-grow:1; }
        .team-card-v .txt h3 { color:#1f2937; font-size:1.2rem; font-weight:700; margin-bottom:5px; }
        .team-card-v .txt .role { color:#d97706; font-weight:600; text-transform:uppercase; font-size:0.85rem; margin-bottom:8px; }
        .team-card-v .txt .bio { color:#6b7280; font-size:0.85rem; line-height:1.5; }
        .team-grid-v { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:25px; align-items:stretch; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.html" class="nav-logo">
                <img src="images/logo.png" alt="Kayunga District Logo" class="logo-img">
                <div class="logo-text">
                    <span class="logo-title">Kayunga District</span>
                    <span class="logo-subtitle">Youth Council</span>
                </div>
            </a>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="nav-menu" id="navMenu">
                <a href="index.html" class="nav-link">Home</a>
                <div class="dropdown">
                    <button class="nav-link dropbtn">About us <i class="fas fa-chevron-down"></i></button>
                    <div class="dropdown-content">
                        <a href="about.php#who-we-are">Who We Are</a>
                        <a href="history.html">Our History</a>
                        <a href="administration.html">Administration</a>
                        <a href="about.php#team">Our Team</a>
                        <a href="about.php#objectives">Objectives</a>
                        <a href="about.php#functions">Functions</a>
                    </div>
                </div>
                <a href="programmes.html" class="nav-link">Programmes</a>
                <a href="impact.html" class="nav-link">Our Impact</a>
                <a href="opportunities.html" class="nav-link">Opportunities</a>
                <a href="contact.html" class="nav-link">Contact</a>
                <a href="login.html" class="nav-link nav-link-auth">Sign In</a>
                <a href="register.html" class="nav-link btn-nav">Join Us</a>
            </div>
        </div>
    </nav>

    <section class="page-header">
        <div class="container">
            <h1>About Us</h1>
            <p>Learn more about Kayunga District Youth Council</p>
        </div>
    </section>

    <section class="who-we-are" id="who-we-are">
        <div class="container">
            <div class="who-we-are-grid">
                <div class="who-we-are-content">
                    <h2>Who We Are</h2>
                    <p>Kayunga District Youth Council is a statutory body established as an umbrella organization of all Youth in Kayunga District between the ages of 18-30 years. We are mandated to organize, mobilize and engage Youth in development activities as well as protect them from any kind of manipulation.</p>
                    <p>The Council is the lead, District umbrella body charged with the organization, protection and unifying of the youth of Kayunga District to harness their potential through raising their national consciousness, creating awareness on their health, skills development and labour productivity for sustainable and youth responsive development.</p>
                </div>
                <div class="who-we-are-image">
                    <img src="images/about1.jpg" alt="Youth Meeting">
                </div>
            </div>
        </div>
    </section>

    <section class="team-section section-light-alt" id="team">
        <div class="container">
            <div class="section-header">
                <h2>Our <span class="text-primary">Team</span></h2>
                <p>We are a dynamic group of individuals passionate about youth empowerment</p>
            </div>
            <div class="team-grid-v">
                <?php 
                $db = getDB();
                $stmt = $db->query("SELECT name, position, bio, photo FROM team WHERE status = 'active' ORDER BY order_num ASC");
                $team = $stmt->fetchAll();
                foreach($team as $m) {
                    $img = $m['photo'] ? 'uploads/team/'.$m['photo'] : 'images/logo.png';
                    echo '<div class="team-card-v">';
                    echo '<div class="img"><img src="'.$img.'" alt="'.$m['name'].'" onerror="this.src=\'images/logo.png\'"></div>';
                    echo '<div class="txt">';
                    echo '<h3>'.$m['name'].'</h3>';
                    echo '<p class="role">'.$m['position'].'</p>';
                    echo '<p class="bio">'.$m['bio'].'</p>';
                    echo '</div></div>';
                }
                ?>
            </div>
        </div>
    </section>

    <section class="objectives-section" id="objectives">
        <div class="container">
            <div class="section-header">
                <h2>Our <span class="text-primary">Objectives</span></h2>
            </div>
            <div class="objectives-grid">
                <div class="objective-card">
                    <div class="objective-number">01</div>
                    <h3>Organize</h3>
                    <p>To organize the youth of Kayunga District in a unified body.</p>
                </div>
                <div class="objective-card">
                    <div class="objective-number">02</div>
                    <h3>Engage</h3>
                    <p>To engage the youth in activities that are of benefit to them and the district.</p>
                </div>
                <div class="objective-card">
                    <div class="objective-number">03</div>
                    <h3>Protect</h3>
                    <p>To protect the youth against any kind of manipulation.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="functions-section section-dark" id="functions">
        <div class="container">
            <div class="section-header">
                <h2>Our <span class="text-primary">Functions</span></h2>
            </div>
            <div class="functions-list">
                <div class="function-item">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h3>Inspire Unity</h3>
                        <p>To inspire and promote among youth a spirit of unity and national consciousness.</p>
                    </div>
                </div>
                <div class="function-item">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h3>Communication</h3>
                        <p>To provide a unified system through which the youth may communicate.</p>
                    </div>
                </div>
                <div class="function-item">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h3>Service Delivery</h3>
                        <p>To establish channels for economic and social services to reach the youth.</p>
                    </div>
                </div>
                <div class="function-item">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h3>Development</h3>
                        <p>To encourage youth role in district development.</p>
                    </div>
                </div>
                <div class="function-item">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h3>Formation</h3>
                        <p>To initiate and encourage formation of youth organizations.</p>
                    </div>
                </div>
                <div class="function-item">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h3>International Relations</h3>
                        <p>To promote relations with youth organizations internationally.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="rights-section">
        <div class="container">
            <div class="section-header">
                <h2>Rights of the <span class="text-primary">Youth</span></h2>
            </div>
            <div class="rights-grid">
                <div class="right-item">
                    <i class="fas fa-users"></i>
                    <h3>Representation</h3>
                    <p>Be represented in relevant decision-making structures and bodies at all levels.</p>
                </div>
                <div class="right-item">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>Education</h3>
                    <p>Both formal and informal education.</p>
                </div>
                <div class="right-item">
                    <i class="fas fa-futbol"></i>
                    <h3>Recreation</h3>
                    <p>Recreation and leisure.</p>
                </div>
                <div class="right-item">
                    <i class="fas fa-briefcase"></i>
                    <h3>Employment</h3>
                    <p>Equal employment opportunities.</p>
                </div>
                <div class="right-item">
                    <i class="fas fa-heartbeat"></i>
                    <h3>Health</h3>
                    <p>Health and welfare services.</p>
                </div>
                <div class="right-item">
                    <i class="fas fa-handshake"></i>
                    <h3>Peace</h3>
                    <p>National and international friendship and peace.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-about">
                    <div class="footer-logo">
                        <img src="images/logo.png" alt="Kayunga District Logo">
                        <span>Kayunga District Youth Council</span>
                    </div>
                    <p>Working to bring smiles and hope to our district's youth through empowerment, education, and engagement.</p>
                </div>
                <div class="footer-social">
                    <h3>Follow Us</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="programmes.html">Programmes</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> Kayunga District</li>
                        <li><i class="fas fa-phone"></i> +256 123 456789</li>
                        <li><i class="fas fa-envelope"></i> info@kayungayouth.go.ug</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Kayunga District Youth Council. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>