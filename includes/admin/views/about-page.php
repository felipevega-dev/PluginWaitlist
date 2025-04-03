<div class="wrap waitlist-about-page">
    <h1>Acerca de Lista de Espera para WooCommerce</h1>
    
    <div class="about-header">
        <div class="about-text">
            <p>Lista de Espera para WooCommerce es un plugin que permite a los clientes registrarse para recibir notificaciones cuando productos agotados vuelvan a estar disponibles.</p>
            <p><strong>Versión:</strong> <?php echo WAITLIST_VERSION; ?></p>
        </div>
        <div class="about-logo">
            <img src="<?php echo WAITLIST_PLUGIN_URL . 'assets/img/logo.png'; ?>" alt="Logo de Gearlabs" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzAwNjZDQyIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE4IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSI+TGlzdGEgZGUgRXNwZXJhPC90ZXh0Pjwvc3ZnPg=='" />
        </div>
    </div>
    
    <div class="about-section">
        <h2>Características principales</h2>
        <div class="about-features">
            <div class="feature-column">
                <div class="feature-item">
                    <div class="feature-icon dashicons dashicons-email-alt"></div>
                    <div class="feature-content">
                        <h3>Sistema de suscripción independiente</h3>
                        <p>Los clientes pueden registrarse para recibir notificaciones cuando los productos agotados vuelvan a estar disponibles.</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon dashicons dashicons-admin-appearance"></div>
                    <div class="feature-content">
                        <h3>Plantillas de email personalizables</h3>
                        <p>Personaliza las notificaciones que reciben tus clientes con colores, logotipos y mensajes propios.</p>
                    </div>
                </div>
            </div>
            
            <div class="feature-column">
                <div class="feature-item">
                    <div class="feature-icon dashicons dashicons-admin-tools"></div>
                    <div class="feature-content">
                        <h3>Gestión de suscriptores</h3>
                        <p>Interfaz de administración completa para ver, filtrar y gestionar los suscriptores por productos.</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon dashicons dashicons-media-spreadsheet"></div>
                    <div class="feature-content">
                        <h3>Importación y exportación</h3>
                        <p>Exporta los datos de suscriptores a Excel para su análisis o uso en otras plataformas.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="about-section">
        <h2>Créditos</h2>
        <div class="credits">
            <div class="credit-item">
                <h3>Desarrollado por</h3>
                <p><strong>Felipe Vega</strong></p>
                <p>Desarrollador principal del plugin y responsable de la implementación de todas las funcionalidades.</p>
                <div class="social-links">
                    <a href="https://www.linkedin.com/in/felipevega-dev/" target="_blank" class="social-link">
                        <span class="dashicons dashicons-linkedin"></span>
                    </a>
                    <a href="https://github.com/felipevega-dev" target="_blank" class="social-link2">
                        <img src="<?php echo WAITLIST_PLUGIN_URL . 'assets/img/github.png'; ?>" alt="GitHub" class="social-icon">
                    </a>
                </div>
            </div>
            
            <div class="credit-item">
                <h3>Empresa</h3>
                <p><strong>Gearlabs</strong></p>
                <p>Especialistas en la creación de software para la realización de distintas actividades.</p>
                <div class="social-links">
                    <a href="https://www.linkedin.com/company/gear-labs/" target="_blank" class="social-link">
                        <span class="dashicons dashicons-linkedin"></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="about-section">
        <h2>Soporte técnico</h2>
        <p>Si necesitas ayuda o tienes alguna consulta sobre el plugin, por favor contacta con nuestro equipo de soporte:</p>
        <ul>
            <li>Email: <a href="mailto:administracion@gearlabs.cl">administracion@gearlabs.cl</a></li>
            <li>Web: <a href="https://www.gearlabs.cl/" target="_blank">www.gearlabs.cl</a></li>
        </ul>
    </div>
</div>

<style>
    .waitlist-about-page {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }
    
    .about-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        background: #fff;
        padding: 25px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .about-text {
        flex: 2;
        padding-right: 30px;
    }
    
    .about-text p {
        font-size: 16px;
        line-height: 1.6;
    }
    
    .about-logo {
        flex: 1;
        text-align: center;
    }
    
    .about-logo img {
        max-width: 150px;
        height: auto;
    }
    
    .about-section {
        background: #fff;
        padding: 25px;
        margin-bottom: 25px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .about-section h2 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        color: #0066CC;
    }
    
    .about-features {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        margin-top: 20px;
    }
    
    .feature-column {
        flex: 0 0 48%;
    }
    
    .feature-item {
        display: flex;
        margin-bottom: 25px;
    }
    
    .feature-icon {
        font-size: 30px;
        color: #0066CC;
        margin-right: 15px;
        flex: 0 0 30px;
    }
    
    .feature-content h3 {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 16px;
    }
    
    .feature-content p {
        margin-top: 0;
        color: #555;
    }
    
    .credits {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
    }
    
    .credit-item {
        flex: 1;
        min-width: 250px;
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 4px;
        border-left: 4px solid #0066CC;
    }
    
    .credit-item h3 {
        margin-top: 0;
        font-size: 16px;
        color: #333;
    }
    
    .social-links {
        margin-top: 15px;
        display: flex;
        gap: 10px;
    }
    
    .social-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background-color: #0066CC;
        color: white;
        border-radius: 50%;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .social-link2 {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background-color:rgb(255, 255, 255);
        color: white;
        border-radius: 50%;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    

    .social-link2:hover {
        background-color:rgb(117, 182, 31);
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .social-link2 .dashicons {
        font-size: 18px;
        width: 18px;
        height: 18px;
    }

    .social-link:hover {
        background-color: #004C99;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .social-link .dashicons {
        font-size: 18px;
        width: 18px;
        height: 18px;
    }
    
    @media screen and (max-width: 782px) {
        .about-header {
            flex-direction: column;
        }
        
        .about-text {
            padding-right: 0;
            margin-bottom: 20px;
        }
        
        .about-features {
            flex-direction: column;
        }
        
        .feature-column {
            flex: 0 0 100%;
        }
    }
</style> 