<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscription Active</title>
</head>
<body>
    <p>Hi {{ $subscription->user->name }},</p>

    <p>Your subscription for <strong>{{ $subscription->plan->name }}</strong> is now active.</p>

    <p>It will stay active until <strong>{{ $subscription->ends_at->toDayDateTimeString() }}</strong>.</p>

    <p>Thanks for using SubEngine.</p>
</body>
</html>