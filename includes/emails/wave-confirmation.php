<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibimos tu respuesta</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f9f9f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px 20px; border-radius: 8px; margin-top: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #eeeeee; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #1a1a1a; font-size: 24px; font-weight: 600; }
        .content { margin-bottom: 30px; font-size: 16px; }
        .success-icon { color: #28a745; font-size: 48px; text-align: center; margin-bottom: 20px; display: block; }
        .next-step { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 15px; margin-top: 20px; }
        .footer { font-size: 13px; color: #666666; border-top: 1px solid #eeeeee; padding-top: 20px; text-align: center; }
        .footer p { margin: 5px 0; }
        @media only screen and (max-width: 600px) { .container { padding: 20px 15px; margin: 0; border-radius: 0; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="success-icon">✓</span>
            <h1>Respuesta Recibida</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{first_name}}</strong>,</p>
            <p>Confirmamos que hemos recibido correctamente tu respuesta a la evaluación <strong>{{wave_index}}</strong>.</p>
            <p style="color: #666; font-size: 14px;">Enviado el: {{submitted_at}}</p>
            
            <p>¡Gracias por tu tiempo y compromiso! Tu participación es muy valiosa.</p>
            
            <div class="next-step">
                <p style="margin: 0;"><strong>Próxima toma:</strong> {{next_wave_index}}</p>
                <p style="margin: 5px 0 0;">Fecha estimada: {{next_due_at}}</p>
            </div>
            
            <p style="margin-top: 20px; font-size: 14px;">Te enviaremos un recordatorio cuando esté disponible.</p>
        </div>
        <div class="footer">
            <p>Estudio: {{survey_name}}</p>
            <p>Si tienes alguna consulta, escribe a: <a href="mailto:{{investigator_email}}" style="color: #666;">{{investigator_email}}</a></p>
        </div>
    </div>
</body>
</html>