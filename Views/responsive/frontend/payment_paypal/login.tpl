<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <title>{config name=shopName}</title>
    <script>
        {if $PaypalIdentity && $PaypalUserLoggedIn}
        window.opener.location.href = "{url controller=checkout}";
        {elseif $PaypalIdentity && !$PaypalFinishRegister}
        window.opener.location.href = "{url controller=register}";
        {/if}
        window.close();
    </script>
</head>

<body></body>
</html>
