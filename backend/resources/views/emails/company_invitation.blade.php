<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Convite para Empresa</title>
</head>

<body>
    <h1>Convite para se juntar à empresa {{ $companyName }}</h1>

    <p>Foi convidado para se juntar à empresa {{ $companyName }}.</p>

    <p>Clique no botão abaixo para aceitar o convite:</p>

    <!-- <p>
        <a href="{{ $inviteUrl }}" style="padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none;">
            Aceitar Convite
        </a>
    </p> -->
    <p>Para aceitar o convite, copie e cole o link abaixo no navegador:</p>

    <p style="word-break: break-all;">
        <a href="{{ $inviteUrl }}">{{ $inviteUrl }}</a>
    </p>


    <p>Se não esperava este convite, pode ignorar este email.</p>
</body>

</html>