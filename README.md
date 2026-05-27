# SubEngine

A subscription billing engine created using Laravel 13 that focuses on webhook-driven payments and toggleable recurring billing.

![Laravel](https://img.shields.io/badge/Laravel-13-red)
![PHP](https://img.shields.io/badge/PHP-8.3-blue)
![Stripe](https://img.shields.io/badge/Stripe-Checkout-635BFF)
![License](https://img.shields.io/badge/license-MIT-green)

## The Problem

Subscription billing looks easy until you deal with webhook idempotency, failed charges, recurring billing, and subscription state transitions. 

A real-world billing system cannot assume a payment succeeded instantly. Payments can have late completions, renewals may fail, and webhook payments can arrive after a user has changed their plans.

SubEngine was designed to handle these processes more realistically. 

## Why This Project Exists

Instead of building a feature-heavy SaaS platform, I focused on the core challenges of subscription integration.

Subscription systems are known for being complex because:
- **Asynchronous Payments**: Payments do not always process instantly.
- **Webhook Dependency**: Network latency can mean a delayed arrival of event notifications.
- **Unusual Lifecycles**: Users can come up with unpredictable scenarios.

In this project I focused on handling these types of situations safely.

## System Architecture & Key Decisions

### Webhook-Driven Activation

Subscriptions are never activated based on client-side success messages or frontend redirects. Instead, SubEngine treats Stripe webhooks as the final confirmation of a successful payment. 

```bash
# Simulating the payment payload
curl --request POST \
  --url http://127.0.0.1:8000/api/subscription \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'Authorization: Bearer $TOKEN' \
  --data '{
  "plan_slug": "monthly",
  "auto_renew": true
}'
```

After successfully paying using the checkout URL, the webhooks are received from Stripe to confirm the payment to the backend: 

```text
# Stripe CLI Listener Logs
--> customer.created [evt_1TbWpkQt5n8J4PH0N2gzZbty]
<-- [200] POST http://localhost:8000/api/webhooks/stripe
--> charge.succeeded [evt_3TbWq7Qt5n8J4PH01RieCjQp]
<-- [200] POST http://localhost:8000/api/webhooks/stripe
--> payment_intent.succeeded [evt_3TbWq7Qt5n8J4PH01RM6RNCB]
<-- [200] POST http://localhost:8000/api/webhooks/stripe
--> checkout.session.completed [evt_1TbWq9Qt5n8J4PH0loylSS1H]
<-- [200] POST http://localhost:8000/api/webhooks/stripe
```

Subscriptions are activated only after the backend receives and verifies a valid webhook notification from Stripe. This keeps the payment state consistent.

### Payment History Design

Payment are treated as historical data and are never deleted.

The system saves all payment attempts, including failed and pending payments. This gives better auditability and debugging. 

### Subscription Lifecycle Model

SubEngine gives subscriptions a complete life cycle rather than a simple active/inactive state. 

All subscriptions go through clearly defined states such as pending, active, or expired. This allows the system to replicate real-world behavior. 

### Strategic Renewal Timing (super important!) 

Renewals are attempted before the subscription actually expires. If the renewal is successful, it would extend and update the next renewal date. If the renewal fails, the subscription's auto-renew would be toggled off and the subscription would automatically expire once the expiration date hits. An email would also be sent to the user notifying them about their failed renewal.

### Scheduler & Idempotency Design

By storing the timestamps for actions including renewals, reminders, and expiration handling, scheduler jobs are designed to be idempotent, meaning that they can run multiple times without causing duplicate actions. This ensures that the scheduler can safely run repeatedly without causing repeated billing actions or duplicate emails.

## System Flow

```txt
User
  ↓
Selects Plan
  ↓
Laravel API creates Stripe Checkout Session
  ↓
Payment completed through Stripe
  ↓
Stripe sends verified webhook
  ↓
Subscription activated
  ↓
Scheduler manages reminders + renewals
```

Billing flow is centred around webhook verification instead of frontend confirmation.

The backend creates the checkout session, and the subscription only activates after receiving and verifying a valid webhook from Stripe.

```txt
Pending
   ↓
Active
   ↓
Renewing
 ↙       ↘
Success   Failure
   ↓         ↓
Extended   Expire
```

Subscriptions are modeled around complete lifecycles. This helps with tracking renewals and handling late webhooks.

```txt
renewal_due_at  → renewal attempt begins
ends_at         → subscription access ends
```

Renewals are attempted before the actual expiration date. Separating the renewal date from the subscription expiration date allows the system to cleanly attempt a renewal before expiration, preventing a user from getting extra unpaid hours.

## Technical Highlights

SubEngine is built as an API-first Laravel backend. Key highlights include: 
- Laravel 13
- PHP 8.3
- MySQL
- Laravel Sanctum authentication
- Stripe Checkout integration
- Stripe webhook verification
- Queued notifications
- Laravel Scheduler
- Authorization policies
- Payment history preservation

## Installation and Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com
   cd SubEngine
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Note: Open `.env` and set your database credentials and Stripe API keys (`STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_WEBHOOK_SECRET`).*

4. **Migrate database**
   ```bash
   php artisan migrate --seed
   ```

5. **Run queue + scheduler**
   ```bash
   # In a separate terminal window to handle async webhooks
   php artisan queue:work
   
   # In another window to run the idempotency tasks
   php artisan schedule:work
   ```

6. **Start Stripe CLI forwarding**
   ```bash
   # In a separate terminal window to route live webhook events
   stripe listen --forward-to localhost:8000/api/webhooks/stripe
   ```

7. **Start server**
   ```bash
   php artisan serve
   ```

## Conclusion

I intentionally built SubEngine to be compact. Instead of a feature-heavy SaaS, the goal was to strictly focus on the most important backend logic that often breaks in real-world applications. 

This project was less about subscription management itself, and more about engineering a resilient backend architecture that remains solid under chaotic conditions.


