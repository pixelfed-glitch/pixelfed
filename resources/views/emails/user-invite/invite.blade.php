<x-mail::message>
# You've been invited to join Pixelfed!

<x-mail::panel>
An account has invited you to join Pixelfed.

{{ $verify->message }}

</x-mail::panel>

<x-mail::button :url="$verify->url()">
Accept Invite
</x-mail::button>

Thanks,<br>
Pixelfed

<small>This email is automatically generated. Please do not reply to this message.</small>
</x-mail::message>
