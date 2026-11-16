<?php
/**
 * Estavia Risk & Security Solutions Ltd — Homepage (database-driven).
 * Services are loaded from MySQL with a static fallback if the DB is down.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

secure_session_start();

/* ---- Service icons: key => inline SVG body ---- */
function icon_svg(string $key): string {
    $paths = [
        'clipboard' => '<path d="M9 3h6l4 4v14H5V3z"/><path d="M9 12l2 2 4-4"/>',
        'search'    => '<circle cx="10" cy="8" r="4"/><path d="M2 21v-1a6 6 0 0112 0v1z"/><path d="M17 8l5 5M22 8l-5 5"/>',
        'audit'     => '<path d="M12 2l8 3v6c0 5-3.4 9.4-8 11-4.6-1.6-8-6-8-11V5l8-3z"/><path d="M8.5 12l2.3 2.3L15.5 9.5"/>',
        'truck'     => '<path d="M1 8h12v9H1zM13 10h4l3 3v4h-7z"/><circle cx="5.5" cy="18.5" r="1.8"/><circle cx="16.5" cy="18.5" r="1.8"/>',
        'map'       => '<path d="M3 5l6-2 6 3 6-2v15l-6 2-6-3-6 2z"/><path d="M9 3v15M15 6v15"/><path d="M12 9a2 2 0 100 4 2 2 0 000-4zM12 13v3"/>',
        'crowd'     => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20v-1a5 5 0 0110 0v1zM14 20v-1a4 4 0 016 0v1z"/>',
        'monitor'   => '<rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>',
        'install'   => '<path d="M2 7h13v10H2z"/><path d="M15 10h4l3 3v4h-7z"/><path d="M6 3v2M10 3v2"/>',
        'wrench'    => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"/>',
        'guard'     => '<circle cx="12" cy="7" r="4"/><path d="M4 21v-1a8 8 0 0116 0v1z"/><path d="M9 11l3 3 3-3"/>',
        'training'  => '<rect x="2" y="5" width="20" height="12" rx="2"/><path d="M9 21h6M12 17v4M8 11h5M8 8h8"/>',
        'port'      => '<path d="M3 18h18M5 18V9l7-5 7 5v9"/><path d="M9 18v-5h6v5"/>',
        'warehouse' => '<path d="M3 21V9l9-6 9 6v12"/><path d="M7 21v-8h10v8M11 17h2"/>',
        'badge'     => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="11" r="2.5"/><path d="M14 9h4M14 13h4M6 17c.7-1.6 1.7-2.4 3-2.4s2.3.8 3 2.4"/>',
        'car'       => '<path d="M5 16l1.5-5A2 2 0 018.4 9.5h7.2a2 2 0 011.9 1.5L19 16"/><path d="M3 16h18v3h-2M5 16v3H3v-3z"/><circle cx="7.5" cy="19" r="1.6"/><circle cx="16.5" cy="19" r="1.6"/>',
        'chat'      => '<circle cx="12" cy="12" r="9"/><path d="M9 10h6M9 14h4M12 3a9 9 0 010 18"/>',
        'drone'     => '<circle cx="12" cy="10" r="3"/><path d="M12 4v3M5.6 6.6l2.1 2.1M18.4 6.6l-2.1 2.1M8 13l-4 3 2 .8L7 20l1.5-3.5M16 13l4 3-2 .8L17 20l-1.5-3.5"/>',
        'fire'      => '<path d="M12 3c2 3 6 4 6 9a6 6 0 01-12 0c0-2 .8-3.6 2-5 .4 1 1 1.7 2 2 0-2.5.8-4.5 2-6z"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
    ];
    $body = $paths[$key] ?? $paths['audit'];
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $body . '</svg>';
}

/* ---- Load services from the database (fallback to static list) ---- */
$services = [];
$dbError = false;
try {
    $services = db()->query(
        'SELECT icon_key, name, description FROM services WHERE is_active = 1 ORDER BY display_order'
    )->fetchAll();
} catch (Throwable $ex) {
    error_log('Services query failed: ' . $ex->getMessage());
    $dbError = true;
}
if (!$services) {
    // Static fallback so the page still renders if the DB is unreachable
    $services = [
        ['icon_key' => 'audit', 'name' => 'Guarding Services', 'description' => 'Well-trained, disciplined and professional security personnel.'],
        ['icon_key' => 'monitor', 'name' => 'Control Room Management', 'description' => '24/7 monitoring, rapid response and incident coordination.'],
        ['icon_key' => 'truck', 'name' => 'Cargo Escort (Local & Transit)', 'description' => 'Safe and secure movement of your valuable cargo.'],
        ['icon_key' => 'port', 'name' => 'Port Operations Security', 'description' => 'Secure operations at the port and surrounding areas.'],
        ['icon_key' => 'clipboard', 'name' => 'Risk Assessments', 'description' => 'Identify, assess and mitigate risks for a safer tomorrow.'],
        ['icon_key' => 'clock', 'name' => '24/7 Security Operations & Support', 'description' => 'Always on. Always alert.'],
    ];
}

$pageTitle = 'Estavia Risk & Security Solutions Ltd — Protecting Assets. Securing Trust.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="Estavia Risk & Security Solutions Ltd offers guarding services, security training, cargo escort, port operations security, CCTV & control room management in Mombasa, Kenya." />
  <meta name="theme-color" content="#0a1628" />
  <link rel="icon" type="image/png" href="assets/logo-transparent.png" />
  <!-- Open Graph (social sharing) -->
  <meta property="og:type" content="website" />
  <meta property="og:title" content="Estavia Risk & Security Solutions Ltd" />
  <meta property="og:description" content="Guarding, cargo escort, port security, CCTV & control room management across Kenya. Protecting Assets. Securing Trust." />
  <meta property="og:image" content="assets/hero.jpg" />
  <!-- Structured data for search engines -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "Estavia Risk & Security Solutions Ltd",
    "description": "Security guarding, cargo escort, port operations security, CCTV and control room management in Kenya.",
    "telephone": "+254750856829",
    "email": "estaviarisksecurity@gmail.com",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Ganjoni, Off Moi Avenue",
      "addressLocality": "Mombasa",
      "addressCountry": "KE"
    },
    "areaServed": "Kenya",
    "openingHours": "Mo-Su 00:00-24:00"
  }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

  <!-- ======= Header ======= -->
  <header class="site-header" id="top">
    <div class="container nav-wrap">
      <a href="#top" class="brand">
        <img src="assets/logo-transparent.png" alt="Estavia Risk and Security Solutions Ltd logo" class="brand-logo" />
        <span class="brand-text">
          <strong>ESTAVIA</strong>
          <small>Risk &amp; Security Solutions Ltd</small>
        </span>
      </a>
      <nav class="main-nav" id="main-nav">
        <a href="#services">Services</a>
        <a href="#why-us">Why Us</a>
        <a href="#process">Our Process</a>
        <a href="#sectors">Sectors</a>
        <a href="#faq">FAQ</a>
        <a href="#contact">Contact</a>
        <a href="#contact" class="btn btn-sm btn-gold nav-cta">Request Quote</a>
      </nav>
      <button class="nav-toggle" id="nav-toggle" aria-label="Toggle menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <!-- ======= Hero ======= -->
  <section class="hero">
    <div class="hero-overlay"></div>
    <div class="container hero-inner">
      <p class="hero-kicker">Risk &amp; Security Solutions Ltd</p>
      <h1>Your Safety,<br /><span class="gold">Our Priority</span></h1>
      <p class="hero-tag">Protecting Assets. Securing Trust.</p>
      <p class="hero-lead">From guarding and investigations to port operations, cargo escort
        and 24/7 control room management — Estavia delivers disciplined,
        professional security across Kenya.</p>
      <div class="hero-actions">
        <a href="#services" class="btn btn-gold">Explore Our Services</a>
        <a href="#contact" class="btn btn-outline">Request a Quote</a>
      </div>
      <ul class="hero-badges">
        <li><strong>24/7</strong><span>Operations &amp; Support</span></li>
        <li><strong>19</strong><span>Security Solutions</span></li>
        <li><strong>100%</strong><span>Vetted Personnel</span></li>
      </ul>
    </div>
  </section>

  <!-- ======= Intro strip ======= -->
  <section class="intro-strip">
    <div class="container intro-grid">
      <div class="intro-item">
        <svg viewBox="0 0 24 24" class="ic"><path d="M12 2l8 3v6c0 5-3.4 9.4-8 11-4.6-1.6-8-6-8-11V5l8-3z"/><path d="M8.5 12l2.3 2.3L15.5 9.5"/></svg>
        <h3>Professional &amp; Reliable</h3>
        <p>Disciplined, well-trained security personnel you can depend on.</p>
      </div>
      <div class="intro-item">
        <svg viewBox="0 0 24 24" class="ic"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
        <h3>Tailored Solutions</h3>
        <p>Security plans designed around your unique risks and needs.</p>
      </div>
      <div class="intro-item">
        <svg viewBox="0 0 24 24" class="ic"><path d="M12 2l8 3v6c0 5-3.4 9.4-8 11-4.6-1.6-8-6-8-11V5l8-3z"/></svg>
        <h3>24/7 Support</h3>
        <p>Always on. Always alert. Rapid response around the clock.</p>
      </div>
      <div class="intro-item">
        <svg viewBox="0 0 24 24" class="ic"><path d="M16 11a4 4 0 10-8 0"/><path d="M2 21v-1a7 7 0 0114 0v1z" transform="translate(3 0)"/></svg>
        <h3>Your Security Partner</h3>
        <p>A long-term partner in protecting your people and assets.</p>
      </div>
    </div>
  </section>

  <!-- ======= Stats ======= -->
  <section class="stats-band">
    <div class="container stats-grid">
      <div class="stat"><strong class="counter" data-target="19">0</strong><span>Integrated Services</span></div>
      <div class="stat"><strong class="counter" data-target="24">0</strong><span>Hour Response</span></div>
      <div class="stat"><strong class="counter" data-target="100">0</strong><span>% Vetted Officers</span></div>
      <div class="stat"><strong class="counter" data-target="7">0</strong><span>Days a Week</span></div>
    </div>
  </section>

  <!-- ======= Services (from database) ======= -->
  <section class="services" id="services">
    <div class="container">
      <div class="section-head">
        <p class="kicker">What we do</p>
        <h2>Our <span class="gold">Services</span></h2>
        <p class="section-sub">Nineteen integrated security solutions — from risk assessment
          to drone surveillance — built to protect every part of your operation.</p>
      </div>
      <div class="services-grid">
        <?php foreach ($services as $svc): ?>
        <article class="service-card">
          <div class="service-ic"><?= icon_svg($svc['icon_key']) ?></div>
          <h3><?= e($svc['name']) ?></h3>
          <p><?= e($svc['description']) ?></p>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ======= Why us ======= -->
  <section class="why" id="why-us">
    <div class="container why-grid">
      <div class="why-copy">
        <p class="kicker">Why Estavia</p>
        <h2>A partner you can <span class="gold">trust</span> with what matters most</h2>
        <p>Estavia Risk &amp; Security Solutions Ltd combines disciplined personnel,
           modern technology and proven procedures to deliver security that works —
           at the port, in your warehouse, on the road and at your event.</p>
        <ul class="why-list">
          <li>Vetted, trained and supervised security officers</li>
          <li>Integrated guarding, monitoring and response</li>
          <li>Port, logistics and industrial specialists</li>
          <li>Single point of contact, 24/7</li>
        </ul>
        <a href="#contact" class="btn btn-gold">Talk to Our Team</a>
      </div>
      <div class="why-media">
        <img src="assets/control-room.jpg" alt="Estavia control room monitoring operations" />
        <div class="why-badge">
          <strong>24/7</strong>
          <span>Security<br />Operations</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= Process (how we work) ======= -->
  <section class="process" id="process">
    <div class="container">
      <div class="section-head light">
        <p class="kicker">How we work</p>
        <h2>Our <span class="gold">Process</span></h2>
        <p class="section-sub">A disciplined, four-step approach to securing your operation.</p>
      </div>
      <ol class="process-grid">
        <li class="process-step">
          <span class="step-num">01</span>
          <h3>Assess</h3>
          <p>We visit your site, analyse threats and vulnerabilities, and document your risks.</p>
        </li>
        <li class="process-step">
          <span class="step-num">02</span>
          <h3>Design</h3>
          <p>A tailored security plan: personnel, technology, procedures and response protocols.</p>
        </li>
        <li class="process-step">
          <span class="step-num">03</span>
          <h3>Deploy</h3>
          <p>Vetted officers and equipment are deployed with clear supervision and reporting lines.</p>
        </li>
        <li class="process-step">
          <span class="step-num">04</span>
          <h3>Monitor &amp; Improve</h3>
          <p>24/7 oversight, incident reporting and regular audits keep your security effective.</p>
        </li>
      </ol>
    </div>
  </section>

  <!-- ======= Sectors we serve ======= -->
  <section class="sectors" id="sectors">
    <div class="container">
      <div class="section-head">
        <p class="kicker">Who we serve</p>
        <h2>Sectors We <span class="gold">Protect</span></h2>
      </div>
      <div class="sectors-grid">
        <div class="sector-card"><h3>Ports &amp; Logistics</h3><p>Port operations, cargo escort, warehouse loss control and transit security.</p></div>
        <div class="sector-card"><h3>Corporate &amp; Commercial</h3><p>Office guarding, access control, background checks and executive protection.</p></div>
        <div class="sector-card"><h3>Events &amp; Hospitality</h3><p>Crowd management, VIP security and guest transfers for functions of any size.</p></div>
        <div class="sector-card"><h3>Industrial &amp; Residential</h3><p>Factories, estates and homes — patrolled, monitored and protected.</p></div>
      </div>
    </div>
  </section>

  <!-- ======= Gallery ======= -->
  <section class="gallery" id="gallery">
    <div class="container">
      <div class="section-head light">
        <p class="kicker">Our operations</p>
        <h2>In the <span class="gold">Field</span></h2>
      </div>
      <div class="gallery-carousel" id="gallery-carousel" role="region" aria-roledescription="carousel" aria-label="Our operations">
        <div class="gallery-viewport" id="gallery-viewport">
          <div class="gallery-grid" id="gallery-track" aria-live="off">
            <figure><img src="assets/guarding.jpg" alt="Guarding services" /><figcaption>Guarding Services</figcaption></figure>
            <figure><img src="assets/cctv.jpg" alt="CCTV and monitoring" /><figcaption>CCTV &amp; Monitoring</figcaption></figure>
            <figure><img src="assets/port.jpg" alt="Port operations" /><figcaption>Port Operations</figcaption></figure>
            <figure><img src="assets/control-room.jpg" alt="Control room" /><figcaption>Control Room</figcaption></figure>
            <figure><img src="assets/cargo.jpg" alt="Heavy cargo truck accompanied by an escort vehicle" /><figcaption>Cargo Escort</figcaption></figure>
            <figure><img src="assets/training.jpeg" alt="Instructor leading a training session" /><figcaption>Training</figcaption></figure>
          </div>
        </div>
        <div class="gallery-controls">
          <button class="gallery-control" id="gallery-prev" type="button" aria-label="Previous operations image">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
          </button>
          <button class="gallery-control gallery-toggle" id="gallery-toggle" type="button" aria-label="Pause automatic slideshow" aria-pressed="false">Pause</button>
          <button class="gallery-control" id="gallery-next" type="button" aria-label="Next operations image">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= FAQ ======= -->
  <section class="faq" id="faq">
    <div class="container faq-grid">
      <div class="faq-copy">
        <p class="kicker">Questions</p>
        <h2>Frequently Asked <span class="gold">Questions</span></h2>
        <p>Can't find what you're looking for? Call us on
          <a href="tel:<?= e(SITE_PHONE_1) ?>"><?= e(SITE_PHONE_1) ?></a> — we're available 24/7.</p>
      </div>
      <div class="faq-list">
        <details class="faq-item">
          <summary>How quickly can you deploy guards to our site?</summary>
          <p>For standard guarding contracts we can deploy within 24–48 hours of signing.
             Emergency deployments in Mombasa can often be arranged the same day.</p>
        </details>
        <details class="faq-item">
          <summary>Which areas do you cover?</summary>
          <p>We are headquartered in Mombasa and operate across Kenya — including port,
             logistics corridors and upcountry transit routes for cargo escorts.</p>
        </details>
        <details class="faq-item">
          <summary>Are your security officers vetted and trained?</summary>
          <p>Yes. Every officer passes background checks and vetting before deployment,
             and completes our structured training and supervision programme.</p>
        </details>
        <details class="faq-item">
          <summary>Do you provide equipment as well as personnel?</summary>
          <p>Yes — we supply, install and maintain CCTV, access control, alarms and
             other safety equipment as part of an integrated security plan.</p>
        </details>
        <details class="faq-item">
          <summary>How do I get a quote?</summary>
          <p>Fill in the request form below, call us, or WhatsApp
             <a href="tel:<?= e(SITE_PHONE_1) ?>"><?= e(SITE_PHONE_1) ?></a>.
             We'll respond with a tailored proposal.</p>
        </details>
      </div>
    </div>
  </section>

  <!-- ======= Contact ======= -->
  <section class="contact" id="contact">
    <div class="container contact-grid">
      <div class="contact-copy">
        <p class="kicker">Contact us</p>
        <h2>Let's secure <span class="gold">your world</span></h2>
        <p>Reach out for a free risk consultation or a tailored security proposal.</p>
        <ul class="contact-list">
          <li>
            <svg viewBox="0 0 24 24"><path d="M5 3h4l2 5-2.5 1.5a12 12 0 005 5L15 12l5 2v4a2 2 0 01-2 2A16 16 0 013 5a2 2 0 012-2z"/></svg>
            <div><span>Phone</span><a href="tel:<?= e(SITE_PHONE_1) ?>">0750 856 829</a> &nbsp;|&nbsp; <a href="tel:<?= e(SITE_PHONE_2) ?>">0729 394 652</a></div>
          </li>
          <li>
            <svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 7l10 6 10-6"/></svg>
            <div><span>Email</span><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></div>
          </li>
          <li>
            <svg viewBox="0 0 24 24"><path d="M12 21s-7-5.5-7-11a7 7 0 0114 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>
            <div><span>Location</span><?= e(SITE_ADDRESS) ?></div>
          </li>
        </ul>
        <?php if ($dbError): ?>
        <p class="db-warning">Note: the database is currently unreachable — showing cached content.</p>
        <?php endif; ?>
      </div>
      <form class="contact-form" id="quote-form" method="post" action="api/quote.php">
        <h3>Request a Quote</h3>
        <?= csrf_field() ?>
        <!-- Honeypot: hidden from humans, bots fill it in and get silently rejected -->
        <div class="hp-field" aria-hidden="true">
          <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off" /></label>
        </div>
        <div class="form-row">
          <input type="text" name="name" placeholder="Your name *" required maxlength="100" />
          <input type="tel" name="phone" placeholder="Phone number *" required maxlength="20" />
        </div>
        <input type="email" name="email" placeholder="Email address" maxlength="120" />
        <select name="service" required>
          <option value="" disabled selected>Select a service *</option>
          <?php foreach ($services as $svc): ?>
          <option><?= e($svc['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <textarea name="message" rows="4" placeholder="Tell us about your security needs" maxlength="2000"></textarea>
        <button type="submit" class="btn btn-gold btn-block">Send Request</button>
        <p class="form-note" id="form-note"></p>
      </form>
    </div>
  </section>

  <!-- ======= Footer ======= -->
  <footer class="site-footer">
    <div class="container footer-grid">
      <div class="footer-brand">
        <img src="assets/logo-transparent.png" alt="Estavia logo" />
        <p class="footer-tag">Protecting Assets.<br /><span class="gold">Securing Trust.</span></p>
      </div>
      <div>
        <h4>Quick Links</h4>
        <a href="#services">Our Services</a>
        <a href="#why-us">Why Us</a>
        <a href="#process">Our Process</a>
        <a href="#faq">FAQ</a>
        <a href="#contact">Contact</a>
      </div>
      <div>
        <h4>Contact</h4>
        <p>0750 856 829 | 0729 394 652</p>
        <p><?= e(SITE_EMAIL) ?></p>
        <p><?= e(SITE_ADDRESS) ?></p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <span id="year"></span> Estavia Risk &amp; Security Solutions Ltd. All rights reserved.</p>
    </div>
  </footer>

  <!-- Floating WhatsApp button -->
  <a class="wa-float" href="https://wa.me/254750856829?text=Hello%20Estavia%2C%20I%27d%20like%20a%20security%20quote."
     target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5.1-1.3A10 10 0 1012 2zm0 1.8a8.2 8.2 0 11-4.2 15.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 0112 3.8zm-3.1 4c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.1s.9 2.4 1 2.6c.1.2 1.8 2.9 4.5 3.9 2.2.9 2.7.7 3.2.7.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2-.1-.1-.2-.2-.5-.3l-1.7-.8c-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 01-3.3-2.9c-.1-.2 0-.4.1-.5l.5-.6c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5L9.4 8c-.1-.3-.3-.5-.5-.5l-.9-.1z"/></svg>
  </a>

  <script src="js/script.js"></script>
</body>
</html>
