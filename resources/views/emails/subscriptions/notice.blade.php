<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>
<body>
    <p>Hi {{ $subscription->user->name }},</p>

    <p><strong>{{ $title }}</strong></p>

    <p>{{ $bodyText }}</p>

    <p>Plan: {{ $subscription->plan->name }}</p>
    <p>Status: {{ $subscription->status }}</p>
    <p>Ends at: {{ $subscription->ends_at?->toDayDateTimeString() }}</p>
</body>
</html>