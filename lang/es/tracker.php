<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package     mod_tracker
 * @category    mod
 * @author Clifford Tham, Valery Fremaux > 1.8
 */

// tracker.php - Moodle 2.7

$string['abandonned'] = 'Abandonado';
$string['action'] = 'Acción';
$string['activeplural'] = 'Actividades';
$string['addacomment'] = 'Añadir un comentario';
$string['addanoption'] = 'Añadir una opción';
$string['addaquerytomemo'] = 'Añadir esta consulta a \"mis consultas\"';
$string['addawatcher'] = 'Añadir un seguimiento';
$string['addtothetracker'] = 'Añadir a este Tacker';
$string['addtothetracker'] = 'Añadirme a este tracker';
$string['administration'] = 'Administración';
$string['administrators'] = 'Administradores';
$string['alltracks'] = 'Ver mi trabajo en todos los trackers';
$string['AND'] = 'Y';
$string['any'] = 'Todos';
$string['askraise'] = 'Pedir a los responsables elevar la prioridad';
$string['assignedto'] = 'Asignar a';
$string['assignee'] = 'Asignado';
$string['attributes'] = 'Atributos';
$string['browse'] = 'Visualizar Tickets';
$string['browser'] = 'Visualizador';
$string['build'] = 'Version';
$string['by'] = 'asignado por';
$string['cascade'] = 'Enviar a un nivel superior';
$string['cascadedticket'] = 'Transferido desde: ';
$string['cced'] = 'Implicados';
$string['ccs'] = 'Implicar';
$string['checkbox'] = 'Casilla de verificación'; // @DYNA
$string['checkboxhoriz'] = 'Casilla de verificación horizontal'; // @DYNA
$string['chooselocal'] = 'Escoger un tracker local como padre';
$string['chooseremote'] = 'Escoger un host remoto';
$string['chooseremoteparent'] = 'Escoger una instancia remota';
$string['clearsearch'] = 'Limpiar los criterios de búsqueda';
$string['comment'] = 'Comentario';
$string['comments'] = 'Comentarios';
$string['component'] = 'Componente';
$string['count'] = 'Contar';
$string['countbyassignee'] = 'Por asignación';
$string['countbymonth'] = 'Por fecha mensual de creación';
$string['countbyreporter'] = 'Por informador';
$string['countbystate'] = 'Informe por estado';
$string['createdinmonth'] = 'Creado en el mes actual';
$string['createnewelement'] = 'Crear un nuevo elemento';
$string['currentbinding'] = 'Cascada actual';
$string['database'] = 'Base de datos';
$string['datereported'] = 'Fecha del informe';
$string['defaultassignee'] = 'Asignación por defecto';
$string['deleteattachedfile'] = 'Borrar adjuntos';
$string['dependancies'] = 'Dependencias';
$string['dependson'] = 'Depende de ';
$string['descriptionisempty'] = 'La descripción está vacia';
$string['doaddelementcheckbox'] = 'Añadir un elemento del tipo casilla de verificación'; // @DYNA
$string['doaddelementcheckboxhoriz'] = 'Añadir un elemento del tipo casilla de verificación horizontal'; // @DYNA
$string['doaddelementdropdown'] = 'Añadir un elemento desplegable'; // @DYNA
$string['doaddelementfile'] = 'Añadir un elemento para fichero adjunto'; // @DYNA
$string['doaddelementradio'] = 'Añadir un elemento de radio'; // @DYNA
$string['doaddelementradiohoriz'] = 'Añadir un elemento de radio en horizontal'; // @DYNA
$string['doaddelementtext'] = 'Añadir un campo simple de texto'; // @DYNA
$string['doaddelementtextarea'] = 'Añadir un campo de área texto'; // @DYNA
$string['doupdateelementcheckbox'] = 'Actualizar un elemento de tipo casilla de verificación'; // @DYNA
$string['doupdateelementcheckboxhoriz'] = 'Actualizar un elemento de tipo casilla de verificación horizontal'; // @DYNA
$string['doupdateelementdropdown'] = 'Actualizar un elemento desplegable';// @DYNA
$string['doupdateelementfile'] = 'Actualizar un elemento para fichero adjunto'; // @DYNA
$string['doupdateelementradio'] = 'Actualizar un elemento de radio'; // @DYNA
$string['doupdateelementradiohoriz'] = 'Actualizar un elemento de radio horizontal'; // @DYNA
$string['doupdateelementtext'] = 'Actualizar un campo simple de texto'; // @DYNA
$string['doupdateelementtextarea'] = 'Actualizar un campo de área de texto'; // @DYNA
$string['dropdown'] = 'Desplegable';
$string['editoptions'] = 'Actualizar opciones';
$string['editproperties'] = 'Actualizar propiedades';
$string['editquery'] = 'Cambiar una consulta archivada';
$string['editwatch'] = 'Cambiar un cc registrado';
$string['elements'] = 'Elementos disponibles';
$string['elementsused'] = 'Elementos utilizados';
$string['elucidationratio'] = 'Elucidation ratio';
$string['emailoptions'] = 'Opciones de Correo';
$string['emergency'] = 'Consulta urgente';
$string['emptydefinition'] = 'El objetivo del tracker no ha sido definido.';
$string['enablecomments_help'] = 'Cuando esta opción esta activa, los seguidores de peticiones pueden añadir comentarios en el tracker.';
$string['enablecomments'] = 'Permitir comentarios';
$string['erroraddissueattribute'] = 'No puede añadirse detalles al asunto(s). Asunto {$a} ';
$string['erroralreadyinuse'] = 'Elemento ya en uso';
$string['errorannotdeletecarboncopies'] = 'No pueden borrarse dobles copias para el usuario: {$a}';
$string['errorannotdeletequeryid'] = 'No se puede borrar consulta con id: {$a}';
$string['errorbadlistformat'] = 'Sólo números (o un listado de números separados por coma (",") permitido en campo de nº del asunto';
$string['errorcannotaddelementtouse'] = 'Cannot add element to list of elements to use for this tracker';
$string['errorcannotclearelementsforissue'] = 'Could not clear elements for issue {$a}';
$string['errorcannotcreateelementoption'] = 'No puede crearse elemento opcional';
$string['errorcannotdeletearboncopyforuser'] = 'No puede borrar copia CC {$a->issue} del usuario: {$a->userid}';
$string['errorcannotdeletecc'] = 'No puedo borrar copia CC';
$string['errorcannotdeleteelement'] = 'No puedo borrar elementos de la lista de elementos usados en este tracker';
$string['errorcannotdeleteelementtouse'] = 'No puedo borrar elementos de la lista de elementos usados en este tracker';
$string['errorcannotdeleteolddependancy'] = 'No puedo borrar las antiguas dependencias';
$string['errorcannotdeleteoption'] = 'Error intentando borrar elemento opcional';
$string['errorcannoteditwatch'] = 'No puedo editar esta vista';
$string['errorcannothideelement'] = 'No puedo ocultar elemento del formulario de este tracker';
$string['errorcannotlogoldownership'] = 'No puedo hacer log del antiguo propietario';
$string['errorcannotsaveprefs'] = 'No puedo insertar preferencia';
$string['errorcannotsetparent'] = 'No puedo fijar tracker-padre a este tracker';
$string['errorcannotshowelement'] = 'No puedo mostrar elemento en este formulario de tracker';
$string['errorcannotsubmitticket'] = 'Error al registrar un nuevo ticket';
$string['errorcannotujpdateoptionbecauseused'] = 'No puedo actualizar la opción del elemento porque está siendo usado como atributo en este tema';
$string['errorcannotunbindparent'] = 'No se puede desligar de este tracker';
$string['errorcannotupdateelement'] = 'No se puede actualizar elemento';
$string['errorcannotupdateissuecascade'] = 'No puedo actualizar actualizar asunto para esta cascada';
$string['errorcannotupdateprefs'] = 'No puedo actualizar preferencia del registro';
$string['errorcannotupdatetrackerissue'] = 'No puedo actualizar asunto del tracker';
$string['errorcannotupdatewatcher'] = 'No puedo actualizar onservador';
$string['errorcannotviewelementoption'] = 'No puede ver elementos opcionales';
$string['errorcannotwritecomment'] = 'Error escribiendo comentario';
$string['errorcannotwritedependancy'] = 'No puedo escribir registro de dependencia';
$string['errorcanotaddelementtouse'] = 'No puede añadir elementos a la lista en uso en este tracker';
$string['errorcookie'] = 'Fallo al emitir cookie: {$a}';
$string['errorcoursemisconfigured'] = 'El curso esta desconfigurado';
$string['errorcoursemodid'] = 'El ID del módulo del curso es incorrecto';
$string['errordbupdate'] = 'No puedo actualizar elemento';
$string['errorelementdoesnotexist'] = 'El elemento  no existe';
$string['errorelementinuse'] = 'Elemento ya en uso';
$string['errorfindingaction'] = 'Error: Acción no encontrada: {$a}';
$string['errorinvalidtrackerelementid'] = 'Elemento incorrecto. No puedo editar el elemento id';
$string['errormoduleincorrect'] = 'El módulo del curso es incorrecto';
$string['errornoaccessallissues'] = 'No tiene permiso para ver todos los asuntos.';
$string['errornoaccessissue'] = 'No tiene permiso para ver este asunto.';
$string['errornoeditissue'] = 'No tiene permiso para editar este asunto.';
$string['errorrecordissue'] = 'No puedo subir tema';
$string['errorremote'] = 'Error en el sitio remoto<br/> {$a} ';
$string['errorremotesendingcascade'] = 'Error al enviar en cascada :<br/> {$a}';
$string['errorunabletosabequery'] = 'Imposible salvar consulta como consulta query';
$string['errorunabletosavequeryid'] = 'Imposible actualizar la consulta con id {$a}';
$string['errorupdateelement'] = 'No puedo actualizar elemento';
$string['eventcourse_module_edited'] = 'Tracker editado';
$string['eventcourse_module_list_viewed'] = 'Tracker listado';
$string['eventcourse_module_viewed'] = 'Tracker introducido';
$string['evolution'] = 'Tendencias';
$string['evolutionbymonth'] = 'Evolución del estado';
$string['file'] = 'Archivo adjunto';
$string['follow'] = 'Seguir';
$string['generaltrend'] = 'Tendencia general';
$string['hassolution'] = 'Para este asunto ya ha sido publicada una solución';
$string['hideccs'] = 'Ocultar seguidores';
$string['hidecomments'] = 'Ocultar comentarios';
$string['hidedependancies'] = 'Ocultar dependencias';
$string['hidehistory'] = 'Ocultar historial';
$string['history'] = 'Historial';
$string['iamadeveloper'] = 'Soy un desarrollador';
$string['iamnotadeveloper'] = 'No soy un desarrollador';
$string['icanmanage'] = 'Puedo gestionar asuntos';
$string['icannotmanage'] = 'No puedo gestionar';
$string['icannotreport'] = 'No puedo emitir informes';
$string['icannotresolve'] = 'No puedo resolver';
$string['icanreport'] = 'Puedo emitir informes';
$string['icanresolve'] = 'Se me pueden asignar algunos tickets';
$string['id'] = 'Identificador';
$string['IN'] = 'EN';
$string['intest'] = 'Probando';
$string['intro'] = 'Descripción';
$string['inworkinmonth'] = 'Todavia en progreso';
$string['issueid'] = 'Ticket';
$string['issuename'] = 'Etiqueta del Ticket ';
$string['issuenumber'] = 'Nº Ticket';
$string['issues'] = 'tickets registrados';
$string['issuestoassign'] = 'Tickets para asignar: {$a}';
$string['issuestowatch'] = 'Tickets para ver: {$a}';
$string['knownelements'] = 'Elementos conocidos del formulario del tracker';
$string['listissues'] = 'Ver lista';
$string['local'] = 'Local';
$string['lowerpriority'] = 'Prioridad baja';
$string['lowertobottom'] = 'Bajar al nivel básico';
$string['manageelements'] = 'Gestionar elementos';
$string['managenetwork'] = 'Cascada y configuración de red';
$string['manager'] = 'Gestor';
$string['me'] = 'Mi perfil';
$string['message_bugtracker'] = 'Gracias por su contribución a la mejora constante de este servicio.';
$string['message_taskspread'] = 'Acaba de definir una tarea. No olvide asignarla a alguien en la siguiente pantalla para su reparto.';
$string['message_ticketting_preassigned'] = 'Hemos registrado su consulta. Será asignada tan pronto sea posible.';
$string['message_ticketting'] = 'Hemos registrado su consulta. Ha sido asignada a {$a}.';
$string['mode_bugtracker'] = 'Tracker para errores';
$string['mode_customized'] = 'Tracker personalizado';
$string['mode_taskspread'] = 'Tracker para distribuir tarea';
$string['mode_ticketting'] = 'Tracker de soporte al usuario';
$string['modulename'] = 'Tracker: Servicio de soporte al usuario';
$string['modulenameplural'] = 'Trackers: Servicios de soporte a usuarios';
$string['month'] = 'Mes';
$string['myassignees'] = 'Responsable al que asigné';
$string['myissues'] = 'Tickets que atiendo';
$string['mypreferences'] = 'Mis preferencias';
$string['myprofile'] = 'Mi perfil';
$string['myqueries'] = 'Mis consultas';
$string['mytasks'] = 'Mis tareas';
$string['mytickets'] = 'Mis tickets';
$string['mywatches'] = 'Mis seguimientos';
$string['mywork'] = 'Mi trabajo';
$string['name'] = 'Nombre';
$string['namecannotbeblank'] = 'El Nombre no puede quedar en blanco';
$string['newissue'] = 'Nuevo ticket';
$string['noassignees'] = 'Sin asignar';
$string['nochange'] = 'Dejar sin cambios';
$string['nocomments'] = 'Sin comentarios';
$string['nodata'] = 'Sin información para mostrar.';
$string['nodevelopers'] = 'Sin desarrolladores';
$string['noelements'] = 'Sin elementos';
$string['noelementscreated'] = 'Sin elementos creados';
$string['nofile'] = 'Sin ficheros adjuntos (subidos)';
$string['nofileloaded'] = 'No hay archivos cargados aquín.';
$string['noissuesreported'] = 'No hay tickets aquí';
$string['noissuesresolved'] = 'No hay tickets resueltos';
$string['nolocalcandidate'] = 'No hay candidatos locales para cascada';
$string['nomnet'] = 'La Red Moodle parece no habilitada';
$string['nooptions'] = 'No hay opciones';
$string['noqueryssaved'] = 'No hay consultas archivadas';
$string['noremotehosts'] = 'No hay network host disponible';
$string['noremotetrackers'] = 'No hay trackers remotos disponibles';
$string['noreporters'] = 'No hay remitentes, probablemente no hay asuntos aquí.';
$string['noresolvers'] = 'No hay responsables';
$string['noresolvingissue'] = 'No hay tickets asignados';
$string['notickets'] = 'No hay tickets emitidos.';
$string['noticketsorassignation'] = 'Sin tickets ni asignaciones';
$string['notifications_help'] = 'Este parámetro habilita o des-habilita notificaciones de email del tracker. Si está habilitado algunos eventos o estados cambiados se enviaran correos de email a los usuarios implicados.';
$string['notifications'] = 'Notificaciones';
$string['notrackeradmins'] = 'No hay administradores';
$string['nowatches'] = 'No hay seguidores';
$string['numberofissues'] = 'Recuento de Tickets';
$string['observers'] = 'Observadores';
$string['open'] = 'Abierto';
$string['option'] = 'Opción ';
$string['optionisused'] = 'El ID de esta opción ya se está usando para este elemento.';
$string['options'] = 'Opciones';
$string['order'] = 'Orden';
$string['pages'] = 'Páginas';
$string['pluginadministration'] = 'Admiistración del tracker';
$string['pluginname'] = 'Sistema tracker/Soporte usuario';
$string['posted'] = 'Publicado';
$string['potentialresolvers'] = 'Responsables potenciales';
$string['preferences'] = 'Preferencias';
$string['prefsnote'] = 'Preferencias configura las notificaciones que por defecto se reciben cuando se publica un nuevo asunto (ticket) y cuando uno se registra para realizar el seguimiento de un asunto existente';
$string['print'] = 'Print';
$string['priority'] = 'Prioridad atribuída';
$string['priorityid'] = 'Prioridad';
$string['profile'] = 'Ajustes del usuario';
$string['published'] = 'Publicado';
$string['queries'] = 'Consultas';
$string['query'] = 'Consulta';
$string['queryname'] = 'Etiqueta de la consulta';
$string['radio'] = 'Botones de radio'; // @DYNA
$string['radiohoriz'] = 'Botones de radio en horizontal'; // @DYNA
$string['raisepriority'] = 'Elevar la prioridad';
$string['raiserequestcaption'] = 'Solicitar la elevación de prioridad de un ticket';
$string['raiserequesttitle'] = 'Solicitar una elevación de prioridad';
$string['raisetotop'] = 'elevar al nivel superior';
$string['reason'] = 'Razón';
$string['register'] = 'Seguir este ticket';
$string['reportanissue'] = 'Publicar un ticket';
$string['reportedby'] = 'Publicado por';
$string['reporter'] = 'Remitente';
$string['reports'] = 'Informes';
$string['resolution'] = 'Solución';
$string['resolved'] = 'Resuelto';
$string['resolvedplural'] = 'Resueltos';
$string['resolvedplural2'] = 'Resuelto';
$string['resolver'] = 'Mis asuntos';
$string['resolvers'] = 'Responsables';
$string['resolving'] = 'En proceso de resolución';
$string['runninginmonth'] = 'Running in current month';
$string['saveasquery'] = 'Guardar una consulta';
$string['savequery'] = 'Guardar la consulta';
$string['search'] = 'Buscar';
$string['searchbyid'] = 'Buscar por ID';
$string['searchcriteria'] = 'Buscar por criterio';
$string['searchresults'] = 'Resultados de la búsqueda';
$string['searchwiththat'] = 'Ejecutar esta consulta otra vez';
$string['selectparent'] = 'Seleccionar Padre';
$string['sendrequest'] = 'Enviar petición';
$string['setoncomment'] = 'Enviarme los comentarios';
$string['setwhenopens'] = 'No avisarme cuando se abra';
$string['setwhenpublished'] = 'No avisarme cuando se publique una solución';
$string['setwhenresolves'] = 'No avisarme cuando se resuelva';
$string['setwhentesting'] = 'No avisarme cuando se compruebe un ticket';
$string['setwhenthrown'] = 'No avisarme cuando se abandone';
$string['setwhenwaits'] = 'No avisarme cuando se mantenga en espera';
$string['setwhenworks'] = 'No avisarme cuando se trabaje sobre ello';
$string['sharethiselement'] = 'Compartir este elemento en todo el sitio';
$string['sharing'] = 'Compartido';
$string['showccs'] = 'Ver seguidores';
$string['showcomments'] = 'Ver comentarios';
$string['showdependancies'] = 'Ver dependencias';
$string['showhistory'] = 'Ver historial';
$string['site'] = 'Sitio WEB';
$string['solution'] = 'Solución';
$string['sortorder'] = 'Ordenar';
$string['standalone'] = 'Tracker independiente (soporte al más alto nivel).';
$string['statehistory'] = 'States';
$string['status'] = 'Estado';
$string['strictworkflow'] = 'Flujo de trabajo estricto';
$string['submission'] = 'Se ha publicado de un nuevo ticket en el tracker [{$a}]';
$string['submitbug'] = 'Enviar el ticket';
$string['subtrackers'] = 'Subtrackers';
$string['sum_opened'] = 'Abierto';
$string['sum_posted'] = 'Esperando';
$string['sum_reported'] = 'Publicados';
$string['sum_resolved'] = 'Resuelto';
$string['summary'] = 'Resumen';
$string['supportmode'] = 'Tipo de soporte';
$string['testing'] = 'En proceso de comprobación';
$string['text'] = 'Campo de texto simple'; // @DYNA
$string['textarea'] = 'Campo de área de texto'; // @DYNA
$string['thanks'] = 'Gracias por su contribución a la mejora constante de este servicio.';
$string['thanksdefault'] = 'Gracias por su contribución a la mejora constante de este servicio.';
$string['thanksmessage'] = 'Mensaje de agradecimiento.';
$string['ticketprefix'] = 'Prefijo del Ticket';
$string['tickets'] = 'Tickets';
$string['tracker_cascade_description'] = '<p>Al publicar en este servicio, conoce y acepta que los trackers de $a se reenvien (en cascada) desde los tickets de soporte a un tracker local.</p>';
$string['tracker_cascade_name'] = 'Transporte de tickets de soporte (Tracker Modulo)';
$string['tracker_description'] = '<p>Cuando publica este servicio usted permite a los trackers de {$a} enlazar en cascada los tickets de soporte a un tracker local.</p>';
$string['tracker_name'] = 'Servicios del módulo Tracker';
$string['tracker-levelaccess'] = 'Mis permisos en este tracker';
$string['tracker:addinstance'] = 'Añadir un tracker';
$string['tracker:canbecced'] = 'Puedo ser elegido para cc';
$string['tracker:comment'] = 'Comentar asuntos';
$string['tracker:configure'] = 'Configurar opciones del tracker';
$string['tracker:configurenetwork'] = 'Configurar propiedades de red Moodle';
$string['tracker:develop'] = 'Puedo ser elegido para resolver tickets (responsable)';
$string['tracker:manage'] = 'Gestionar asuntos';
$string['tracker:managepriority'] = 'Gestionar prioridades';
$string['tracker:managewatches'] = 'Gestionar seguimientos de un ticket';
$string['tracker:report'] = 'Informar sobre tickets';
$string['tracker:resolve'] = 'Resolver tickets';
$string['tracker:seeissues'] = 'Ver contenidos de los asuntos';
$string['tracker:shareelements'] = 'Compartir elementos para todo el sitio.';
$string['tracker:viewallissues'] = 'Ver todos los tickets';
$string['tracker:viewpriority'] = 'Ver prioridad de mis propios tickets';
$string['tracker:viewreports'] = 'Ver informes de asuntos de trabajo';
$string['trackerelements'] = 'Definición del Tracker';
$string['trackereventchanged'] = 'Cambio de estado del asunto en el tracker [{$a}]';
$string['trackerhost'] = 'Host Padre para el tracker';
$string['trackername'] = 'Nombre del Tracker';
$string['transfer'] = 'Transferencia';
$string['transfered'] = 'Transferido';
$string['transferservice'] = 'Cascada del soporte de tickets';
$string['turneditingoff'] = 'Desactivar edición';
$string['turneditingon'] = 'Activar edición';
$string['type'] = 'Tipo';
$string['unassigned'] = 'Sin asignar' ;
$string['unbind'] = 'Desplegar en cascada';
$string['unmatchingelements'] = 'Ambas definiciones del tracker no coinciden. Esto podría provocar un resultado inesperado cuando se hagan soporte de tickets en cascada.';
$string['unregisterall'] = 'Desvincularse de todos' ;
$string['unsetoncomment'] = 'Avisarme cuando se envíen comentarios';
$string['unsetwhenopens'] = 'Avisarme cuando se abra';
$string['unsetwhenpublished'] = 'Avisarme cuando una solución se publique';
$string['unsetwhenresolves'] = 'Avisarme cuando se resuelva';
$string['unsetwhentesting'] = 'Avisarme cuando se compruebe un ticket';
$string['unsetwhenthrown'] = 'Avisarme cuando se tira a la papelera';
$string['unsetwhenwaits'] = 'Avisarme cuando se está en espera';
$string['unsetwhenworks'] = 'Avisarme cuando se esté trabajando sobre ello';
$string['urgentraiserequestcaption'] = 'Un usario ha solicitado una petición de Prioridad Urgente';
$string['urgentsignal'] = 'CONSULTA URGENTE';
$string['validated'] = 'Validado';
$string['view'] = 'Vistas';
$string['vieworiginal'] = 'Ver original';
$string['voter'] = 'Votar';
$string['waiting'] = 'En Espera';
$string['watches'] = 'Seguimientos';
$string['youneedanaccount'] = 'Para abrir un ticket aquí, se necesita una cuenta autorizada.';

// help strings

$string['supportmode_help'] = 'El modo soporte aplica algunos parámetros predefinidos y sobreescribe permisos en roles relacionados con el tracker para cambiar su comportamiento.

* Informe de errores: Los informadores tienen acceso para leer TODOS los tickes de la lista en un modo colaborativo. 
Todos los estados están disponibles para el seguimiento de las incidencias, incluidas acciones en sistemas de prueba.

* Soporte de usuario/Ticketting: Los informadores solo tienen normalmente acceso a los tickets que han enviado y no puede acceder al modo navegación del tracker. 
Algunos cambios de estados han sido deshabilitados, es lo más común cuando se usa para acciones técnicas

* Reparto de tareas: Los informadores no tienen acceso a todo el sistema de distribución de ticktes. Los usuarios solo pueden acceder a los tickes que tienen asignados en mis tickets. 
No tendrán acceso a la navegación de tickets, algunas funciones de estados intermedios no están habilitadas en el modo simple de asignación de estados.

* Personalizado: Cuando se personaliza, el editor de actividades puede elegir estados y sobrescribir para aplicarlos al tracker. 
Éste es el modo más flexible pero necesita conocer bien el sistema de roles de Moodle y su gestión.
';

$string['strictworkflow_help'] = '
Cuando se activa, cada rol específico dentro del tracker (roles del tracker) sólo tendrá acceso a los estados que depende de dicho rol.';

?>
