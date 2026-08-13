<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
        .header { text-align: center; margin-bottom: 30px; }
        .footer { margin-top: 30px; font-size: 0.8em; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>SC Inmobiliaria</h2>
        </div>
        <p>Hola <strong>{{ $ownerName }}</strong>,</p>
        <p>Espero que te encuentres bien. Adjunto a este correo enviamos el comprobante de la liquidación de alquiler correspondiente al periodo actual.</p>
        <p>Si tienes alguna duda o consulta, por favor no dudes en contactarnos.</p>
        <p>Saludos cordiales,</p>
        <p><strong>El equipo de SC Inmobiliaria</strong></p>
        <div class="footer">
            <p>Este es un mensaje automático, por favor no responder directamente a este correo.</p>
        </div>
    </div>
</body>
</html>
