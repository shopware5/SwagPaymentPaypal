<!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8">

    <title>{config name=shopName}</title>
    <script>
{if $PaypalIdentity && $PaypalUserLoggedIn}
        (function ($) {
            $('.modal form[name=existing_customer]').submit();
        })(window.opener.jQuery);
{elseif $PaypalIdentity && !$PaypalFinishRegister}
        window.opener.location.href = "{url controller=register}";
{/if}
        window.close();
    </script>
</head>

<body></body>
</html>
