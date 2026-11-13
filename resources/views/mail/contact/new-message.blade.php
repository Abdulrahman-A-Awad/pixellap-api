<x-mail::message>
# New contact message

**{{ $msg->name }}** sent a message through the website.

<x-mail::table>
| | |
|:--|:--|
| **Name** | {{ $msg->name }} |
| **Email** | {{ $msg->email }} |
| **Phone** | {{ $msg->phone ?: '—' }} |
| **Company** | {{ $msg->company ?: '—' }} |
| **Service** | {{ $service ?: '—' }} |
| **Language** | {{ $msg->locale === 'ar' ? 'Arabic' : 'English' }} |
| **Received** | {{ $msg->created_at?->format('d M Y, H:i') }} |
</x-mail::table>

<x-mail::panel>
{{ $msg->message }}
</x-mail::panel>

<x-mail::button :url="$adminUrl">
Open in admin
</x-mail::button>

Reply to this email to answer {{ $msg->name }} directly.
</x-mail::message>
