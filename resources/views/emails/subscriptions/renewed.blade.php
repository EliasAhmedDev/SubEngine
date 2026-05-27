<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscription Renewed</title>
</head>
<body>
    <p>Hi {{ $subscription->user->name }},</p>

    <p>Your subscription for <strong>{{ $subscription->plan->name }}</strong> has been renewed successfully.</p>

    <p>Next billing date: <strong>{{ $subscription->next_billing_at->toDayDateTimeString() }}</strong></p>

    <p>Thanks for using SubEngine.</p>
</body>
</html>