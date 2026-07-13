<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seu portal está pronto</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1e293b;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:28px 28px 12px;">
                        <p style="margin:0 0 8px;font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Portal Fornecedor On Demand</p>
                        <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;">Olá, {{ $nomeAdmin }}</h1>
                        <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                            O portal da <strong>{{ $razaoSocial }}</strong> foi criado com sucesso.
                            A partir de agora, compras, fornecedores e contratações ficam centralizados em um só lugar.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 20px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;">
                            <tr>
                                <td style="padding:18px 20px;">
                                    <p style="margin:0 0 8px;font-size:13px;color:#1d4ed8;font-weight:600;">Seu endereço exclusivo</p>
                                    <p style="margin:0 0 12px;font-size:18px;line-height:1.4;font-weight:700;word-break:break-all;">
                                        <a href="{{ $portalUrl }}" style="color:#1d4ed8;text-decoration:none;">{{ $portalUrl }}</a>
                                    </p>
                                    <p style="margin:0;font-size:14px;line-height:1.5;color:#475569;">
                                        Guarde este endereço. É por ele que você e sua equipe acessam o portal no dia a dia.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 20px;">
                        <h2 style="margin:0 0 12px;font-size:16px;">Como entrar</h2>
                        <ol style="margin:0;padding-left:20px;font-size:15px;line-height:1.7;color:#334155;">
                            <li>Acesse <a href="{{ $loginUrl }}" style="color:#2563eb;">{{ $loginUrl }}</a></li>
                            <li>Use o e-mail <strong>{{ $emailAdmin }}</strong></li>
                            <li>Use a senha que você definiu no cadastro</li>
                        </ol>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 20px;">
                        <h2 style="margin:0 0 12px;font-size:16px;">Primeiros passos</h2>
                        <ul style="margin:0;padding-left:20px;font-size:15px;line-height:1.7;color:#334155;">
                            <li>Crie sua primeira contratação</li>
                            <li>Envie para análise de Compras</li>
                            <li>Centralize fornecedores e notas de serviço</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 24px;">
                        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
                            Você está no período de <strong>15 dias grátis</strong>, válido até
                            <strong>{{ $trialEndsAt->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</strong>.
                        </p>
                        <a href="{{ $loginUrl }}"
                           style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 20px;border-radius:8px;">
                            Entrar no meu portal
                        </a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 28px 24px;border-top:1px solid #e2e8f0;background:#f8fafc;">
                        <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">
                            Este e-mail foi enviado automaticamente por
                            <strong>{{ config('identidade.welcome_mail.from_name') }}</strong>.
                            Não responda este endereço.
                            @if (config('identidade.welcome_mail.reply_to'))
                                Para suporte, escreva para
                                <a href="mailto:{{ config('identidade.welcome_mail.reply_to') }}" style="color:#2563eb;">
                                    {{ config('identidade.welcome_mail.reply_to') }}
                                </a>.
                            @endif
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
