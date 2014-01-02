<?php
$l['myalerts'] = "MyAlerts";
$l['myalerts_pluginlibrary_missing'] = "El plugin seleccionado no puede ser instalado debido a que <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> no esta instalado.";
$l['myalerts_class_outdated'] = "Clase de Alertas obsoleta. Verifica que el directorio /inc/plugins/MyAlerts/ este actualizado. (Versión de MyAlerts: {1}, Versión de la clase MyAlerts: {2})";

$l['setting_group_myalerts'] = "Configuraciónn de MyAlerts";
$l['setting_group_myalerts_desc'] = "Configuración para el plugin MyAlerts";
$l['setting_myalerts_enabled'] = "Activar MyAlerts";
$l['setting_myalerts_enabled_desc'] = "Este switch puede ser usado para desactivar de manera global todas las características de MyAlerts";
$l['setting_myalerts_perpage'] = "Alertas por página";
$l['setting_myalerts_perpage_desc'] = "¿Cuántas alertas deseas mostrar en la página de la lista de alertas? (por default 10)";
$l['setting_myalerts_dropdown_limit'] = "Número de alertas en el menú desplegable";
$l['setting_myalerts_dropdown_limit_desc'] = "¿Cuántas alertas deseas mostrar el el menú desplegable global? (por default 5)";
$l['setting_myalerts_autorefresh'] = "Página MyAlerts con actualización automática AJAX";
$l['setting_myalerts_autorefresh_desc'] = "¿Cada cuántos segundos deseas actualizar la página de la lista de alertas vía AJAX? (0 sin actualización automática)";
$l['setting_myalerts_alert_rep'] = "Alertas en reputación";
$l['setting_myalerts_alert_rep_desc'] = "¿Deseas que los usuarios reciban una alerta cuando alguien modifica su reputación?";
$l['setting_myalerts_alert_pm'] = "Alertas en Mensajes Privados";
$l['setting_myalerts_alert_pm_desc'] = "¿Deseas que los usuarios reciban una alerta cuando reciban un nuevo Mensaje Privado (MP)?";
$l['setting_myalerts_alert_buddylist'] = "Alertas al ser agregado a Lista de Amigos";
$l['setting_myalerts_alert_buddylist_desc'] = "¿Deseas que los usuarios reciban una alerta cuando sean agregados a la lista de amigos de otro usuario?";
$l['setting_myalerts_alert_quoted'] = "Alertas en citas en temas";
$l['setting_myalerts_alert_quoted_desc'] = "¿Deseas que los usuarios reciban una alerta cuando sean citados en un tema?";
$l['setting_myalerts_alert_post_threadauthor'] = "Alertas de respuestas al autor del tema";
$l['setting_myalerts_alert_post_threadauthor_desc'] = "¿Deseas que los autores reciban una alerta cuando alguien responda a sus temas?";
$l['setting_myalerts_default_avatar'] = "URL de Avatar por default";
$l['setting_myalerts_default_avatar_desc'] = "Especifica una URL a una imagen para usarla como avatar por default en MyAlerts, en usuarios sin avatar.";


$l['myalerts_helpsection_name'] = 'Alertas de Usuario';
$l['myalerts_helpsection_desc'] = 'Información básica relacionada con el sistema de alertas en este sitio.';

$l['myalerts_help_info'] = 'Información Básica';
$l['myalerts_help_info_desc'] = 'Información básica acerca del sistema de alertas y su funcionamiento.';
$l['myalerts_help_info_document'] = 'El sistema de alertas en este sitio te permite ver de una manera simple lo que ha estado sucediendo recientemente en el sitio a través de una notificación.
<p>
	Existe un simple conteo de sus alertas sobresalientes no leídas encontradas en la cabecera del sitio. Haciendo click en el conteo se abrirá un menú desplegable mostrandote una lista de tus alertas no leídas, de la cual podrás ir a la página de la lista de alertas si así lo decides.
</p>
<p>
	La página de la lista de alertas la puedes <a href="usercp.php?action=alerts">encontrar aquí</a> y contiene una lista de todas las alertas que has recibido, tanto leídas como no leídas. También podrás eliminar las alertas antiguas que no desees mantener en la lista.
</p>';

$l['myalerts_help_alert_types'] = 'Tipo de Alertas';
$l['myalerts_help_alert_types_desc'] = 'Información acerca de los diferentes tipos de alertas que se pueden recibir.';
$l['myalerts_help_alert_types_document'] = 'Existen varios tipos de alertas que puedes recibir basadas en las diferentes acciones realizadas en este sitio. Estas son las actuales acciones diferentes con las cuales puedes recibir una alerta:
<br /><br />';

$l['myalerts_task_cleanup_ran'] = 'Alertas leidas con más de una semana fueron eliminadas exitosamente!';
$l['myalerts_task_cleanup_error'] = 'Algo salió mal mientras se limpiaban las alertas...';
$l['myalerts_task_cleanup_disabled'] = 'La limpieza de alertas ha sido desactivada en la configuración.';

$l['myalerts_task_title'] = 'Limpieza de MyAlerts';
$l['myalerts_task_description'] = 'Tarea de limpieza de alertas leidas antiguas. Esta tarea es necesaria o de lo contrario la tabla de alertas podría llegar a ser enorme.';
?>
