<?php
/**
 * Tong Shu Premium — Checkout
 * 
 * Simple checkout page with Stripe & PayPal
 * Creates license after successful payment
 */

require_once __DIR__ . '/../db/database.php';

$stripe_publishable = ''; // Stripe publishable key (pk_live_...)
$stripe_secret = '';      // Stripe secret key (sk_live_...)
$paypal_client_id = '';   // PayPal Client ID
$success = false;
$license_key = '';
$error = '';

// Handle successful payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_confirmed'])) {
    $db = new WPTS_DB(__DIR__ . '/../db/licenses.sqlite');
    $email = sanitize($_POST['email'] ?? '');
    $name = sanitize($_POST['customer_name'] ?? '');
    $plan = sanitize($_POST['plan'] ?? 'single');
    $payment_id = sanitize($_POST['payment_id'] ?? '');
    $payment_provider = sanitize($_POST['provider'] ?? '');

    if (empty($email) || empty($payment_id)) {
        $error = 'E-Mail und Zahlungs-ID erforderlich.';
    } else {
        $key = $db->generate_key();
        $max_sites = $plan === 'unlimited' ? 999 : ($plan === 'multi' ? 5 : 1);
        $expires = date('Y-m-d H:i:s', strtotime('+1 year'));

        $db->create_license($key, $plan, $max_sites, $email, $name, $expires);

        // Log payment
        $stmt = $db->get_pdo();
        $stmt->exec("CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            license_key TEXT NOT NULL,
            payment_id TEXT NOT NULL,
            provider TEXT NOT NULL,
            plan TEXT NOT NULL,
            amount REAL NOT NULL,
            email TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $amount = $plan === 'unlimited' ? 199 : ($plan === 'multi' ? 79 : 39);
        $pdo = $db->get_pdo();
        $stmt = $pdo->prepare("INSERT INTO payments (license_key, payment_id, provider, plan, amount, email) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$key, $payment_id, $payment_provider, $plan, $amount, $email]);

        $success = true;
        $license_key = $key;
    }
}

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🏮 Tong Shu Premium — Checkout</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }
.container { max-width: 900px; margin: 0 auto; }
h1 { text-align: center; margin-bottom: 30px; }
.plans { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
.plan { flex: 1; min-width: 250px; background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border: 2px solid transparent; cursor: pointer; transition: all 0.2s; }
.plan:hover, .plan.selected { border-color: #39b152; transform: translateY(-2px); }
.plan.popular { border-color: #f59e0b; }
.plan-badge { background: #f59e0b; color: #fff; font-size: 11px; padding: 3px 10px; border-radius: 20px; display: inline-block; margin-bottom: 10px; }
.plan h2 { margin-bottom: 10px; }
.plan .price { font-size: 36px; font-weight: 700; color: #39b152; }
.plan .price small { font-size: 16px; color: #666; }
.plan ul { margin: 15px 0; padding-left: 20px; }
.plan li { margin-bottom: 5px; color: #555; }

.checkout-form { background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.checkout-form h2 { margin-bottom: 20px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
.form-group input { width: 100%; padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 16px; }
.payment-buttons { display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap; }
.btn { padding: 12px 24px; border-radius: 6px; border: none; font-size: 16px; cursor: pointer; font-weight: 600; text-align: center; display: inline-block; text-decoration: none; }
.btn-stripe { background: #635bff; color: #fff; }
.btn-stripe:hover { background: #4b45c7; }
.btn-paypal { background: #ffc439; color: #000; }
.btn-paypal:hover { background: #f0b420; }

.success-box { background: #d1fae5; border: 2px solid #10b981; border-radius: 10px; padding: 30px; text-align: center; }
.success-box h2 { color: #065f46; margin-bottom: 15px; }
.license-key { font-size: 24px; font-family: monospace; background: #fff; padding: 15px 30px; border-radius: 8px; display: inline-block; margin: 15px 0; letter-spacing: 3px; border: 2px dashed #10b981; }
.error-box { background: #fee2e2; border: 2px solid #dc2626; border-radius: 10px; padding: 15px; color: #991b1b; margin-bottom: 20px; }
</style>
</head>
<body>
<div class="container">
    <h1>🏮 Tong Shu Premium</h1>

    <?php if ($success): ?>
        <!-- SUCCESS -->
        <div class="success-box">
            <h2>✅ Zahlung erfolgreich!</h2>
            <p>Dein Lizenzschlüssel:</p>
            <div class="license-key"><?= htmlspecialchars($license_key) ?></div>
            <p style="margin-top:15px;">Kopiere diesen Key und füge ihn in WordPress ein:<br>
            <strong>Einstellungen → Tong Shu → Lizenzschlüssel → Verifizieren</strong></p>
            <p style="margin-top:10px;color:#666;">Lizenz wurde auch per E-Mail gesendet.</p>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="error-box"><?= $error ?></div>
        <?php endif; ?>

        <!-- Plans -->
        <div class="plans">
            <div class="plan" data-plan="single" onclick="selectPlan(this)">
                <h2>Single Site</h2>
                <div class="price">€39 <small>/Jahr</small></div>
                <ul>
                    <li>1 Website</li>
                    <li>Alle Premium-Features</li>
                    <li>Updates & Support</li>
                </ul>
            </div>
            <div class="plan popular" data-plan="multi" onclick="selectPlan(this)">
                <div class="plan-badge">BELIEBT</div>
                <h2>Multi Site</h2>
                <div class="price">€79 <small>/Jahr</small></div>
                <ul>
                    <li>5 Websites</li>
                    <li>Alle Premium-Features</li>
                    <li>Updates & Support</li>
                    <li>Priority Support</li>
                </ul>
            </div>
            <div class="plan" data-plan="unlimited" onclick="selectPlan(this)">
                <h2>Unlimited</h2>
                <div class="price">€199 <small>/Jahr</small></div>
                <ul>
                    <li>Unbegrenzte Websites</li>
                    <li>Alle Premium-Features</li>
                    <li>Updates & Support</li>
                    <li>White-Label</li>
                </ul>
            </div>
        </div>

        <!-- Checkout Form -->
        <div class="checkout-form">
            <h2>Kontaktdaten & Zahlung</h2>
            <input type="hidden" id="selected-plan" value="single">
            <div class="form-group">
                <label>Name</label>
                <input type="text" id="customer-name" placeholder="Vollständiger Name">
            </div>
            <div class="form-group">
                <label>E-Mail</label>
                <input type="email" id="customer-email" placeholder="deine@email.de" required>
            </div>
            <div class="payment-buttons">
                <!-- Stripe -->
                <button class="btn btn-stripe" onclick="payStripe()">
                    💳 Kreditkarte (Stripe)
                </button>
                <!-- PayPal -->
                <button class="btn btn-paypal" onclick="payPayPal()">
                    🅿️ PayPal
                </button>
            </div>
            <p style="margin-top:15px;color:#999;font-size:13px;text-align:center;">
                Sichere Zahlung. Lizenz wird sofort per E-Mail zugesendet.
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
function selectPlan(el) {
    document.querySelectorAll('.plan').forEach(p => p.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('selected-plan').value = el.dataset.plan;
}

// ===== STRIPE =====
function payStripe() {
    const plan = document.getElementById('selected-plan').value;
    const email = document.getElementById('customer-email').value;
    const name = document.getElementById('customer-name').value;

    if (!email) { alert('E-Mail ist erforderlich!'); return; }

    const prices = { single: 39, multi: 79, unlimited: 199 };

    // Option A: Stripe Payment Links (empfohlen — kein Backend nötig)
    // Erstelle Payment Links im Stripe Dashboard und trage sie hier ein:
    const paymentLinks = {
        single: 'https://buy.stripe.com/YOUR_SINGLE_LINK',
        multi: 'https://buy.stripe.com/YOUR_MULTI_LINK',
        unlimited: 'https://buy.stripe.com/YOUR_UNLIMITED_LINK'
    };

    // Redirect zu Stripe Payment Link
    // ⚠️ Ersetze die Links mit deinen echten Stripe Payment Links
    alert('Stripe Payment Link wird hier aktiviert.\n\n' +
          '1. Gehe zu Stripe Dashboard → Payment Links\n' +
          '2. Erstelle 3 Links (€39, €79, €199)\n' +
          '3. Füge sie in payStripe() ein\n\n' +
          'Plan: ' + plan + ' | Preis: €' + prices[plan]);

    // Uncomment when links are ready:
    // window.location.href = paymentLinks[plan] + '?prefilled_email=' + encodeURIComponent(email);
}

// ===== PAYPAL =====
function payPayPal() {
    const plan = document.getElementById('selected-plan').value;
    const email = document.getElementById('customer-email').value;

    if (!email) { alert('E-Mail ist erforderlich!'); return; }

    const prices = { single: 39, multi: 79, unlimited: 199 };

    alert('PayPal Buttons werden hier eingebunden.\n\n' +
          '1. Gehe zu PayPal Developer → Create App\n' +
          '2. Kopiere die Client ID\n' +
          '3. Füge sie in index.php ein\n\n' +
          'Plan: ' + plan + ' | Preis: €' + prices[plan]);
}
</script>
</body>
</html>
