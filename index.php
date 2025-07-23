<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ToDoze</title>
<link rel="icon" href="img/favicon.ico">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
--primary: #6c5ce7;
--primary-light: #a29bfe;
--secondary: #00b894;
--accent: #fdcb6e;
--dark: #2d3436;
--light: #f5f6fa;
--text: #2d3436;
--text-light: #636e72;
--border: #dfe6e9;
--shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
--shadow: 0 4px 6px rgba(0,0,0,0.1);
--shadow-md: 0 10px 20px rgba(0,0,0,0.1);
--transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

* {
margin: 0;
padding: 0;
box-sizing: border-box;
}

html {
scroll-behavior: smooth;
}

body {
font-family: 'Poppins', sans-serif;
color: var(--text);
line-height: 1.7;
background-color: white;
overflow-x: hidden;
}

.container {
width: 100%;
max-width: 1200px;
margin: 0 auto;
padding: 0 2rem;
}

/* Header - Glass Morphism */
header {
position: fixed;
top: 0;
left: 0;
width: 100%;
z-index: 1000;
backdrop-filter: blur(10px);
background-color: rgba(255, 255, 255, 0.9);
box-shadow: var(--shadow-sm);
transition: var(--transition);
}

.header-container {
display: flex;
justify-content: space-between;
align-items: center;
padding: 1.5rem 2rem;
}

.logo {
height: 3.5rem;
transition: var(--transition);
}

/* Navigation */
.nav-toggle {
display: none;
background: none;
border: none;
font-size: 1.8rem;
color: var(--dark);
cursor: pointer;
}

.nav-menu {
display: flex;
list-style: none;
gap: 2.5rem;
}

.nav-link {
position: relative;
color: var(--text);
text-decoration: none;
font-weight: 500;
font-size: 1.1rem;
transition: var(--transition);
}

.nav-link::after {
content: '';
position: absolute;
bottom: -5px;
left: 0;
width: 0;
height: 2px;
background-color: var(--primary);
transition: var(--transition);
}

.nav-link:hover {
color: var(--primary);
}

.nav-link:hover::after {
width: 100%;
}

/* Hero Section - Perfect Image Integration */
.hero {
min-height: 100vh;
display: flex;
align-items: center;
padding-top: 6rem;
background: linear-gradient(135deg, #f5f7fa 0%, #e4e7eb 100%);
position: relative;
overflow: hidden;
}

.hero::before {
content: '';
position: absolute;
top: -50%;
right: -50%;
width: 100%;
height: 200%;
background: radial-gradient(circle, rgba(108, 92, 231, 0.1) 0%, rgba(108, 92, 231, 0) 70%);
animation: pulse 15s infinite alternate;
}

@keyframes pulse {
0% { transform: translate(0, 0); }
50% { transform: translate(10%, 10%); }
100% { transform: translate(0, 0); }
}

.hero-content {
display: grid;
grid-template-columns: 1fr 1fr;
align-items: center;
gap: 4rem;
position: relative;
z-index: 2;
}

.hero-text {
position: relative;
}

.hero-image {
position: relative;
border-radius: 1rem;
overflow: hidden;
transform: perspective(1000px) rotateY(-5deg);
transition: var(--transition);
height: 100%;
display: flex;
align-items: center;
justify-content: center;
}

.hero-image img {
width: 100%;
height: auto;
max-height: 500px;
object-fit: contain;
}

.hero-image:hover {
transform: perspective(1000px) rotateY(-2deg);
}

.hero h1 {
font-size: 3.5rem;
margin-bottom: 1.5rem;
line-height: 1.2;
color: var(--dark);
font-weight: 700;
}

.hero p {
font-size: 1.25rem;
color: var(--text-light);
margin-bottom: 2.5rem;
max-width: 90%;
}

.hero-buttons {
display: flex;
gap: 1.5rem;
}

.btn {
display: inline-flex;
align-items: center;
justify-content: center;
padding: 1rem 2rem;
border-radius: 0.5rem;
font-weight: 600;
text-decoration: none;
transition: var(--transition);
font-size: 1.1rem;
}

.btn-primary {
background-color: var(--primary);
color: white;
box-shadow: var(--shadow);
}

.btn-primary:hover {
background-color: var(--primary-light);
transform: translateY(-3px);
box-shadow: var(--shadow-md);
}

.btn-secondary {
background-color: white;
color: var(--primary);
border: 2px solid var(--primary);
}

.btn-secondary:hover {
background-color: var(--primary);
color: white;
transform: translateY(-3px);
}

/* Features Section */
.features {
padding: 8rem 0;
background-color: white;
}

.section-header {
text-align: center;
margin-bottom: 5rem;
}

.section-header h2 {
font-size: 2.5rem;
margin-bottom: 1rem;
color: var(--dark);
}

.section-header p {
font-size: 1.25rem;
color: var(--text-light);
max-width: 700px;
margin: 0 auto;
}

.features-grid {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
gap: 2rem;
}

.feature-card {
background: white;
border-radius: 1rem;
padding: 2.5rem 2rem;
box-shadow: var(--shadow-sm);
transition: var(--transition);
border: 1px solid var(--border);
text-align: center;
position: relative;
overflow: hidden;
}

.feature-card::before {
content: '';
position: absolute;
top: 0;
left: 0;
width: 4px;
height: 0;
background-color: var(--primary);
transition: var(--transition);
}

.feature-card:hover {
transform: translateY(-10px);
box-shadow: var(--shadow-md);
border-color: var(--primary);
}

.feature-card:hover::before {
height: 100%;
}

.feature-icon {
width: 4rem;
height: 4rem;
margin: 0 auto 1.5rem;
background-color: rgba(108, 92, 231, 0.1);
border-radius: 1rem;
display: flex;
align-items: center;
justify-content: center;
}

.feature-icon svg {
width: 2rem;
height: 2rem;
color: var(--primary);
}

.feature-card h3 {
font-size: 1.5rem;
margin-bottom: 1rem;
color: var(--dark);
}

.feature-card p {
color: var(--text-light);
font-size: 1rem;
}

/* Dashboard Section - Enhanced Responsiveness */
.dashboard {
padding: 8rem 0;
background-color: var(--light);
}

.dashboard-container {
max-width: 1000px;
margin: 0 auto;
border-radius: 1rem;
overflow: hidden;
box-shadow: var(--shadow-md);
position: relative;
}

.dashboard-image {
width: 100%;
display: block;
transition: var(--transition);
}

.dashboard-overlay {
position: absolute;
top: 0;
left: 0;
width: 100%;
height: 100%;
background: rgba(0,0,0,0.3);
display: flex;
align-items: center;
justify-content: center;
opacity: 0;
transition: var(--transition);
}

.dashboard-container:hover .dashboard-overlay {
opacity: 1;
}

.dashboard-btn {
padding: 0.75rem 1.5rem;
background-color: var(--primary);
color: white;
border: none;
border-radius: 0.5rem;
font-weight: 500;
cursor: pointer;
transition: var(--transition);
}

.dashboard-btn:hover {
background-color: var(--primary-light);
transform: translateY(-2px);
}

/* About Section */
.about {
padding: 8rem 0;
background-color: white;
}

.about-content {
max-width: 800px;
margin: 0 auto;
text-align: center;
}

.about-content h2 {
font-size: 2.5rem;
margin-bottom: 2rem;
color: var(--dark);
}

.about-content p {
font-size: 1.1rem;
color: var(--text-light);
margin-bottom: 1.5rem;
line-height: 1.8;
}

/* Footer */
footer {
background-color: var(--dark);
color: white;
padding: 6rem 0 3rem;
}

.footer-content {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
gap: 3rem;
margin-bottom: 4rem;
}

.footer-column h3 {
color: white;
margin-bottom: 1.5rem;
font-size: 1.5rem;
position: relative;
display: inline-block;
}

.footer-column h3::after {
content: '';
position: absolute;
bottom: -0.75rem;
left: 0;
width: 3rem;
height: 3px;
background-color: var(--accent);
}

.footer-links {
list-style: none;
}

.footer-links li {
margin-bottom: 1rem;
}

.footer-links a {
color: #b2bec3;
text-decoration: none;
transition: var(--transition);
font-size: 1.1rem;
}

.footer-links a:hover {
color: white;
padding-left: 0.5rem;
}

.copyright {
text-align: center;
padding-top: 3rem;
border-top: 1px solid rgba(255,255,255,0.1);
color: #b2bec3;
font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
.hero-content {
grid-template-columns: 1fr;
text-align: center;
gap: 3rem;
}

.hero-text {
margin-bottom: 3rem;
}

.hero p {
margin: 0 auto 2.5rem;
}

.hero-buttons {
justify-content: center;
}

.hero-image {
order: -1;
max-width: 600px;
margin: 0 auto;
transform: none;
}

.hero-image:hover {
transform: scale(1.02);
}
}

@media (max-width: 768px) {
.container {
padding: 0 1.5rem;
}

.nav-toggle {
display: block;
}

.nav-menu {
position: fixed;
top: 5.5rem;
left: 0;
width: 100%;
background: white;
flex-direction: column;
align-items: center;
padding: 2rem;
box-shadow: var(--shadow-md);
transform: translateY(-150%);
transition: transform 0.3s ease-out;
}

.nav-menu.active {
transform: translateY(0);
}

.nav-link {
padding: 1rem 0;
font-size: 1.2rem;
}

.hero h1 {
font-size: 2.5rem;
}

.hero p {
font-size: 1.1rem;
}

.section-header h2 {
font-size: 2rem;
}

.section-header p {
font-size: 1.1rem;
}

.feature-card {
padding: 2rem 1.5rem;
}
}

@media (max-width: 480px) {
.hero-buttons {
flex-direction: column;
gap: 1rem;
}

.btn {
width: 100%;
}

.hero h1 {
font-size: 2rem;
}

.features-grid {
grid-template-columns: 1fr;
}

.dashboard-overlay {
opacity: 1;
background: rgba(0,0,0,0.5);
}
}
</style>
</head>
<body>
<!-- Header -->
<header id="header">
<div class="container header-container">
<img src="img/logo.png" alt="ToDoze" class="logo">
<button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M3 6H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M3 18H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
</button>
<nav>
<ul class="nav-menu" id="navMenu">
<li><a href="#features" class="nav-link">Features</a></li>
<li><a href="#dashboard" class="nav-link">Dashboard</a></li>
<li><a href="#about" class="nav-link">About</a></li>
<li><a href="users/policy.php" class="nav-link">Policy</a></li>
<li><a href="users/terms.php" class="nav-link">Terms</a></li>
</ul>
</nav>
</div>
</header>

<!-- Hero Section -->
<section class="hero" id="home">
<div class="container">
<div class="hero-content">
<div class="hero-text">
<h1>Simplified Task Management for You and Your Team</h1>
<p>ToDoze helps you stay organized, whether you're managing personal tasks or collaborating with others.</p>
<div class="hero-buttons">
<a href="users/signin.php" class="btn btn-primary">Start Exploring</a>
<a href="#features" class="btn btn-secondary">Learn More</a>
</div>
</div>
<div class="hero-image">
<img src="img/img-about.png" alt="ToDoze Dashboard Preview">
</div>
</div>
</div>
</section>

<!-- Features Section -->
<section class="section features" id="features">
<div class="container">
<div class="section-header">
<h2>Powerful Features</h2>
<p>Everything you need to stay organized and productive</p>
</div>
<div class="features-grid">
<div class="feature-card">
<div class="feature-icon">
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
</svg>
</div>
<h3>Task Management</h3>
<p>Create, organize, and prioritize tasks with our intuitive interface.</p>
</div>
<div class="feature-card">
<div class="feature-icon">
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
</svg>
</div>
<h3>Team Collaboration</h3>
<p>Work together by assigning tasks and tracking progress.</p>
</div>
<div class="feature-card">
<div class="feature-icon">
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
</svg>
</div>
<h3>Set Reminders</h3>
<br>
<p>Set reminders so you never miss a deadline.</p>
</div>
<div class="feature-card">
<div class="feature-icon">
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
</svg>
</div>
<h3>Progress Tracking</h3>
<p>Visualize your productivity with simple, clear reports.</p>
</div>
</div>
</div>
</section>

<!-- Dashboard Section - Enhanced with Interactive Overlay -->
<section class="section dashboard" id="dashboard">
<div class="container">
<div class="section-header">
<h2>Your Productivity Dashboard</h2>
<p>A clean, intuitive interface designed for focus and efficiency</p>
</div>
<div class="dashboard-container">
<img src="img/dash.png" alt="ToDoze Dashboard" class="dashboard-image">
<div class="dashboard-overlay">
<button class="dashboard-btn">Explore Dashboard</button>
</div>
</div>
</div>
</section>

<!-- About Section -->
<section class="section about" id="about">
<div class="container">
<div class="about-content">
<h2>About ToDoze</h2>
<p>Todoze was created to solve a simple problem: most task management tools are either too complex or too limited. We built a solution that's just right - powerful enough for teams but simple enough for personal use.</p>
<p>Our focus is on clean design and intuitive functionality. We've stripped away unnecessary features to give you a tool that helps you get things done without getting in your way.</p>
<p>Whether you're managing your daily to-dos or coordinating a team team, Todoze keeps things straightforward and effective.</p>
</div>
</div>
</section>

<!-- Footer -->
<footer>
<div class="container">
<div class="footer-content">
<div class="footer-column">
<h3>ToDoze</h3>
<p>Easy task management for individuals and teams.</p>
</div>
<div class="footer-column">
<h3>More</h3>
<ul class="footer-links">
<li><a href="#features">Features</a></li>
<li><a href="#dashboard">Dashboard</a></li>
<li><a href="#about">About</a></li>
</ul>
</div>
<div class="footer-column">
<h3>Legal</h3>
<ul class="footer-links">
<li><a href="usres/policy.php">Privacy Policy</a></li>
<li><a href="users/terms.php">Terms of Service</a></li>
</ul>
</div>
</div>
<div class="copyright">
<p>&copy; 2025 ToDoze. All rights reserved.</p>
</div>
</div>
</footer>

<!-- JavaScript -->
<script>
// Mobile navigation toggle
const navToggle = document.getElementById('navToggle');
const navMenu = document.getElementById('navMenu');
const header = document.getElementById('header');

navToggle.addEventListener('click', () => {
navMenu.classList.toggle('active');
navToggle.setAttribute('aria-expanded', navMenu.classList.contains('active'));
});

// Close mobile menu when clicking a link
document.querySelectorAll('.nav-link').forEach(link => {
link.addEventListener('click', () => {
navMenu.classList.remove('active');
navToggle.setAttribute('aria-expanded', 'false');
});
});

// Header scroll effect
window.addEventListener('scroll', () => {
if (window.scrollY > 50) {
header.style.boxShadow = '0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06)';
header.style.background = 'rgba(255,255,255,0.95)';
} else {
header.style.boxShadow = '0 1px 2px 0 rgba(0,0,0,0.05)';
header.style.background = 'rgba(255,255,255,0.9)';
}
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
anchor.addEventListener('click', function(e) {
e.preventDefault();

const target = document.querySelector(this.getAttribute('href'));
if (target) {
window.scrollTo({
top: target.offsetTop - 80,
behavior: 'smooth'
});
}
});
});

// Animate feature cards on scroll
const featureCards = document.querySelectorAll('.feature-card');

const observer = new IntersectionObserver((entries) => {
entries.forEach((entry, index) => {
if (entry.isIntersecting) {
entry.target.style.transitionDelay = `${index * 0.1}s`;
entry.target.style.opacity = '1';
entry.target.style.transform = 'translateY(0)';
}
});
}, { threshold: 0.1 });

featureCards.forEach(card => {
card.style.opacity = '0';
card.style.transform = 'translateY(20px)';
card.style.transition = 'all 0.5s ease';
observer.observe(card);
});

// Dashboard button functionality
const dashboardBtn = document.querySelector('.dashboard-btn');
if (dashboardBtn) {
dashboardBtn.addEventListener('click', () => {
window.location.href = '#features';
});
}
</script>
</body>
</html>