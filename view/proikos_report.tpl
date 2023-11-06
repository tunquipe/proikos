{{ form }}
<style>

    [class^=circle-bar-progress]>svg, [class*=" circle-bar-progress"]>svg {
        width: 200px;
        height: 200px;
    }
    [class^=circle-bar-exam]>svg, [class*=" circle-bar-exam"]>svg {
        width: 150px;
        height: 150px;
    }
    #circle_approved .circle-progress-value {
        stroke-width: 20px;
        stroke: hsl(195, 100%, 44%);
    }
    #circle_approved .circle-progress-circle {
        stroke-width: 20px;
        stroke: hsl(0, 0%, 85%);
    }
    #circle_approved .circle-progress-text {
        fill: hsl(195, 100%, 44%);
        font-weight: 800;
        font-family: 'Gabarito', sans-serif;
        font-size: 1.8rem;
    }

    #circle_disapproved .circle-progress-value {
        stroke-width: 20px;
        stroke: hsl(0, 85%, 45%);
    }
    #circle_disapproved .circle-progress-circle {
        stroke-width: 20px;
        stroke: hsl(0, 0%, 85%);
    }
    #circle_disapproved .circle-progress-text {
        fill: hsl(0, 85%, 45%);
        font-weight: 800;
        font-family: 'Gabarito', sans-serif;
        font-size: 1.8rem;
    }
    #exam_one .circle-progress-value {
        stroke-width: 10px;
        stroke: hsl(37, 100%, 48%);
        stroke-dasharray: 11 2;
    }
    #exam_one .circle-progress-circle {
        stroke-width: 10px;
        stroke: #FFF;
    }
    #exam_one .circle-progress-text {
        font-weight: bold;
        fill: hsl(37, 100%, 48%);
        font-family: 'Gabarito', sans-serif;
        font-size: 2.2rem;
    }
    #exam_two .circle-progress-value {
        stroke-width: 10px;
        stroke: #00a7df;
        stroke-dasharray: 11 2;
    }
    #exam_two .circle-progress-circle {
        stroke-width: 10px;
        stroke: #FFF;
    }
    #exam_two .circle-progress-text {
        font-weight: bold;
        fill: #00a7df;
        font-family: 'Gabarito', sans-serif;
        font-size: 2.2rem;
    }
    .ui-progress-bar {
        position: relative;
        height: 22px;
        padding-right: 2px;
        background-color: #FFF;
        border: 1px solid #cdcd;
    }

    .ui-progress {
        position: relative;
        display: block;
        overflow: hidden;
        height: 20px;
        background-color: #d41111;
        border: 1px solid #d41111;
        -webkit-animation: animate-stripes 2s linear infinite;
    }

    .ui-progress span.ui-label {
        font-size: 1.2em;
        position: absolute;
        right: 0;
        line-height: 20px;
        padding-right: 12px;
        color: rgba(0,0,0,0.6);
        text-shadow: rgba(255,255,255, 0.45) 0 1px 0px;
        white-space: nowrap;
    }
    .counter {
        display: table-cell;
        margin:1.5%;
        font-size:50px;
        border-radius: 50%;
        vertical-align: middle;
    }
    .bg-report{
        background-color: #f9f9f9;
        padding: 2rem;
    }
    #user_register_course{
        max-width: 750px;
        min-height: 400px;
        margin: 10px auto;
    }
    #user_participants_course{
        max-width: 750px;
        min-height: 400px;
        margin: 10px auto;
    }
    #user_certificates_course{
        max-width: 750px;
        min-height: 400px;
        margin: 10px auto;
    }
    #user_approved_course{
        max-width: 750px;
        min-height: 400px;
        margin: 10px auto;
    }
    #user_stakeholders_course{
        max-width: 750px;
        min-height: 400px;
        margin: 10px auto;
    }
    .header01{
        background: #259ffb;
        color: #FFF;
        text-align: center;
        text-transform: uppercase;
    }
    .header02{
        background: #25e6a5;
        color: #FFF;
        text-align: center;
        text-transform: uppercase;
    }
    .header03{
        background: #fe6077;
        color: #FFF;
        text-align: center;
        text-transform: uppercase;
    }
    .footer-table{
        background: #FFF;
        text-transform: uppercase;
        color: #000;
    }
    .table tr th{
        padding: 14px !important;
    }
    .table tr td{
        padding: 14px !important;
    }
</style>
<div class="bg-report">

    <div class="row">
        div.col-md-
    </div>


    <div class="row">
        <div class="col-md-12">
            <h3 class="title">Trabajadores entrenados</h3>

            <div id="counter" class="counter" data-count="0">0</div>

            <div id="progress_bar" class="ui-progress-bar">
                <div class="ui-progress" style="width: 0;">
                </div>
            </div>

            <h3 class="title">Evaluaciones</h3>
            <div class="container-box">
                <div class="row flex-container">
                    <div class="col flex-item">
                        Resultados examen inicial
                    </div>
                    <div class="col flex-item">
                        <div id="exam_one" class="circle-bar-exam">
                        </div>
                    </div>
                </div>

                <div class="row flex-container">
                    <div class="col flex-item">
                        Resultados examen final
                    </div>
                    <div class="col flex-item">
                        <div id="exam_two" class="circle-bar-exam">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">Usuarios aprobados y desaprobados</h3>

                <div class="row flex-container">
                    <div class="col flex-item">
                        <img src="{{ url_plugin_image }}/man_and_woman.png">
                    </div>
                    <div class="col flex-item">
                        <div id="circle_approved" class="circle-bar-progress">
                        </div>
                    </div>
                    <div class="col flex-item">
                        <div id="circle_disapproved" class="circle-bar-progress">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">Usuarios inscritos por cada curso</h3>
                <div id="user_register_course"></div>
            </div>
        </div>
    </div>

    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">Usuarios participantes por cada curso</h3>
                <div id="user_participants_course"></div>
            </div>
        </div>
    </div>

    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">Usuarios participantes que logran culminar satisfactoriamente por curso</h3>
                <div id="user_certificates_course"></div>
            </div>
        </div>
    </div>

    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">Usuarios Aprobados Vs Desaprobados</h3>
                <div id="user_approved_course"></div>
            </div>
        </div>
    </div>

    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">Participación por tipo de Stakeholders</h3>
                <div id="user_stakeholders_course"></div>
            </div>
        </div>
    </div>

    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">Usuarios por Actividad de Formación Acumulado</h3>
                <div id="table_for_activity">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr class="header01">
                                <th>Actividad / Mes</th>
                                <th>Jul</th>
                                <th>Ago</th>
                                <th>Set</th>
                                <th>Oct</th>
                                <th>Nov</th>
                                <th>Dic</th>
                                <th>Ene</th>
                                <th>Feb</th>
                                <th>Mar</th>
                                <th>Abr</th>
                                <th>May</th>
                                <th>Jun</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Inducción HSE</td>
                                <td>538</td>
                                <td>538</td>
                                <td>538</td>
                                <td>538</td>
                                <td>538</td>
                                <td>538</td>
                                <td>538</td>
                                <td>538</td>
                                <td>538</td>
                                <td>538</td>
                                <td>538</td>
                                <td>538</td>
                                <td>538</td>
                            </tr>
                            <tr>
                                <td>Matriz IPERC y Matriz IAEIA</td>
                                <td>26</td>
                                <td>26</td>
                                <td>26</td>
                                <td>26</td>
                                <td>26</td>
                                <td>26</td>
                                <td>26</td>
                                <td>26</td>
                                <td>26</td>
                                <td>26</td>
                                <td>26</td>
                                <td>26</td>
                                <td>26</td>
                            </tr>
                            <tr>
                                <td>Permisos de Trabajo</td>
                                <td>30</td>
                                <td>30</td>
                                <td>30</td>
                                <td>30</td>
                                <td>30</td>
                                <td>30</td>
                                <td>30</td>
                                <td>30</td>
                                <td>30</td>
                                <td>30</td>
                                <td>30</td>
                                <td>30</td>
                                <td>30</td>
                            </tr>
                            <tr class="footer-table">
                                <td>Total Acumulado</td>
                                <td>594</td>
                                <td>594</td>
                                <td>594</td>
                                <td>594</td>
                                <td>594</td>
                                <td>594</td>
                                <td>594</td>
                                <td>594</td>
                                <td>594</td>
                                <td>594</td>
                                <td>594</td>
                                <td>594</td>
                                <td>594</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">HH de Formación - Acumulado</h3>
                <div id="table_for_activity">
                    <table class="table table-bordered table-hover">
                        <thead>
                        <tr class="header02">
                            <th>Actividad / Mes</th>
                            <th>Jul</th>
                            <th>Ago</th>
                            <th>Set</th>
                            <th>Oct</th>
                            <th>Nov</th>
                            <th>Dic</th>
                            <th>Ene</th>
                            <th>Feb</th>
                            <th>Mar</th>
                            <th>Abr</th>
                            <th>May</th>
                            <th>Jun</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Inducción HSE</td>
                            <td>4304</td>
                            <td>4304</td>
                            <td>4304</td>
                            <td>4304</td>
                            <td>4304</td>
                            <td>4304</td>
                            <td>4304</td>
                            <td>4304</td>
                            <td>4304</td>
                            <td>4304</td>
                            <td>4304</td>
                            <td>4304</td>
                            <td>4304</td>
                        </tr>
                        <tr>
                            <td>Matriz IPERC y Matriz IAEIA</td>
                            <td>208</td>
                            <td>208</td>
                            <td>208</td>
                            <td>208</td>
                            <td>208</td>
                            <td>208</td>
                            <td>208</td>
                            <td>208</td>
                            <td>208</td>
                            <td>208</td>
                            <td>208</td>
                            <td>208</td>
                            <td>208</td>
                        </tr>
                        <tr>
                            <td>Permisos de Trabajo</td>
                            <td>240</td>
                            <td>240</td>
                            <td>240</td>
                            <td>240</td>
                            <td>240</td>
                            <td>240</td>
                            <td>240</td>
                            <td>240</td>
                            <td>240</td>
                            <td>240</td>
                            <td>240</td>
                            <td>240</td>
                            <td>240</td>
                        </tr>
                        <tr class="footer-table">
                            <td>Total Acumulado</td>
                            <td>4752</td>
                            <td>4752</td>
                            <td>4752</td>
                            <td>4752</td>
                            <td>4752</td>
                            <td>4752</td>
                            <td>4752</td>
                            <td>4752</td>
                            <td>4752</td>
                            <td>4752</td>
                            <td>4752</td>
                            <td>4752</td>
                            <td>4752</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">% de aprobación por Actividad de Formación</h3>
                <div id="table_for_activity">
                    <table class="table table-bordered table-hover">
                        <thead>
                        <tr class="header03">
                            <th>Actividad / Mes</th>
                            <th>Jul</th>
                            <th>Ago</th>
                            <th>Set</th>
                            <th>Oct</th>
                            <th>Nov</th>
                            <th>Dic</th>
                            <th>Ene</th>
                            <th>Feb</th>
                            <th>Mar</th>
                            <th>Abr</th>
                            <th>May</th>
                            <th>Jun</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Inducción HSE</td>
                            <td>92%</td>
                            <td>92%</td>
                            <td>92%</td>
                            <td>92%</td>
                            <td>92%</td>
                            <td>92%</td>
                            <td>92%</td>
                            <td>92%</td>
                            <td>92%</td>
                            <td>92%</td>
                            <td>92%</td>
                            <td>92%</td>
                            <td>92%</td>
                        </tr>
                        <tr>
                            <td>Matriz IPERC y Matriz IAEIA</td>
                            <td>96%</td>
                            <td>96%</td>
                            <td>96%</td>
                            <td>96%</td>
                            <td>96%</td>
                            <td>96%</td>
                            <td>96%</td>
                            <td>96%</td>
                            <td>96%</td>
                            <td>96%</td>
                            <td>96%</td>
                            <td>96%</td>
                            <td>96%</td>
                        </tr>
                        <tr>
                            <td>Permisos de Trabajo</td>
                            <td>77%</td>
                            <td>77%</td>
                            <td>77%</td>
                            <td>77%</td>
                            <td>77%</td>
                            <td>77%</td>
                            <td>77%</td>
                            <td>77%</td>
                            <td>77%</td>
                            <td>77%</td>
                            <td>77%</td>
                            <td>77%</td>
                            <td>77%</td>
                        </tr>
                        <tr class="footer-table">
                            <td>Total Acumulado</td>
                            <td>88%</td>
                            <td>88%</td>
                            <td>88%</td>
                            <td>88%</td>
                            <td>88%</td>
                            <td>88%</td>
                            <td>88%</td>
                            <td>88%</td>
                            <td>88%</td>
                            <td>88%</td>
                            <td>88%</td>
                            <td>88%</td>
                            <td>88%</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>




<script type="text/javascript">
    $("#report_stakeholders").change(function (){
        let idSelector = $("#report_stakeholders").val();
        if(idSelector == 1 ){
            $('#option-builder').hide();
        } else {
            $('#option-builder').show();
        }
    });

    $(document).ready(function() {
        $('input[name="show_data"][value="1"]').prop('checked', true);
        // Esperar a que el documento se cargue completamente

        let data = {
            gender: $("#report_gender").val(),
            stakeholders: $("#report_stakeholders").val(),
            name_company: $("#report_name_company").val(),
            position_company: $("#report_position_company").val(),
            department: $("#report_department").val(),
            start_date: $("#star_date").val(),
            end_date: $("#end_date").val(),
            show_data: $("#show_data").val()
        };
        loadData(data);

        // Seleccionar el botón con el ID "report_generate" y agregar un controlador de eventos
        $("#report_generate").click(function() {
            event.preventDefault();
            // Acción que se realiza al hacer clic en el botón
            let data = {
                gender: $("#report_gender").val(),
                stakeholders: $("#report_stakeholders").val(),
                name_company: $("#report_name_company").val(),
                position_company: $("#report_position_company").val(),
                department: $("#report_department").val(),
                start_date: $("#star_date").val(),
                end_date: $("#end_date").val(),
                show_data: $("#show_data").val()
            };
            loadData(data);
        });
    });

    function loadData(data){

        let urlCampus = '{{_p.web}}';
        let chartSession;
        let chartParticipants;
        let chartCertificates;
        let chartApproved;
        let chartStakeholders ;
        let circle_approved;
        let circle_disapproved;

        let urlStudent = urlCampus + 'plugin/proikos/src/ajax.php?action=get_students_approved_disapproved';
        let urlSession = urlCampus + 'plugin/proikos/src/ajax.php?action=get_report_session';
        let urlParticipants = urlCampus + 'plugin/proikos/src/ajax.php?action=get_participating_users';
        let urlCertificates = urlCampus + 'plugin/proikos/src/ajax.php?action=get_certificate_users';
        let urlApproved = urlCampus + 'plugin/proikos/src/ajax.php?action=get_course_approved';
        let urlStakeholders = urlCampus + 'plugin/proikos/src/ajax.php?action=get_participating_stakeholders';

        if (chartSession) {
            chartSession.destroy();
        }
        if (chartParticipants) {
            chartParticipants.destroy();
        }
        if (chartCertificates) {
            chartCertificates.destroy();
        }
        if (chartApproved) {
            chartApproved.destroy();
        }
        if (chartStakeholders) {
            chartStakeholders.destroy();
        }
        if (circle_approved) {
            circle_approved.destroy();
        }

        $.ajax({
            type: "POST",
            url: urlStakeholders,
            data: data,
            dataType: "json",
            success: function(response) {
                //console.log(response);
                var jsonData = response;

                var categories = Object.keys(jsonData);
                var dataValues = Object.values(jsonData);


                var options = {
                    series: [{
                        name: 'Usuarios con certificado',
                        data: dataValues,
                    }],
                    chart: {
                        type: 'bar',
                        height: 400,
                        width: 750,
                    },
                    plotOptions: {
                        bar: {
                            columnHeights: '50%',
                            distributed: true,
                            borderRadius: 10,
                            borderRadiusApplication: 'end',
                            horizontal: true,
                            dataLabels: {
                                position: 'top', // top, center, bottom
                            },
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        offsetX: 30,
                        style: {
                            fontSize: '12px',
                            colors: ["#304758"]
                        }
                    },
                    xaxis: {
                        categories: categories,
                    },
                };


                chartStakeholders = new ApexCharts(document.querySelector("#user_stakeholders_course"), options);
                chartStakeholders.render();

            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Error: " + errorThrown);
            }
        });

        $.ajax({
            type: "POST",
            url: urlApproved,
            data: data,
            dataType: "json",
            success: function(response) {

                let jsonData = response;
                let seriesDataApproved = jsonData.map(function (item) {
                    return item.approved;
                });

                let seriesDataDisapproved = jsonData.map(function (item) {
                    return item.disapproved;
                });

                let categories = jsonData.map(function (item) {
                    return item.course_code;
                });


                var options = {
                    series: [{
                        name: 'Usuarios aprobados',
                        data: seriesDataApproved
                    }, {
                        name: 'Usuarios desaprobados',
                        data: seriesDataDisapproved
                    }],
                    chart: {
                        type: 'bar',
                        height: 400,
                        width: 750,
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '50%',
                            borderRadius: 5,
                            borderRadiusApplication: 'end',
                            dataLabels: {
                                position: 'top', // top, center, bottom
                            },
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        offsetY: -30,
                        style: {
                            fontSize: '12px',
                            colors: ["#304758"]
                        }
                    },
                    stroke: {
                        show: true,
                        width: 2,
                        colors: ['transparent']
                    },
                    xaxis: {
                        categories: categories,
                    },
                    fill: {
                        opacity: 1
                    },
                    colors: ['#25e6a5', '#fe6077'],
                    loading: {
                        enabled: true, // Habilitar la precarga
                        type: 'default', // Tipo de precarga ('default', 'light', 'dark')
                        label: 'Cargando datos...', // Texto de la precarga
                    }
                };

                chartApproved = new ApexCharts(document.querySelector("#user_approved_course"), options);
                chartApproved.render();


            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Error: " + errorThrown);
            }
        });

        $.ajax({
            type: "POST",
            url: urlCertificates,
            data: data,
            dataType: "json",
            success: function(response) {
                let jsonData = response;
                let seriesData = jsonData.map(function (item) {
                    return item.certificate;
                });
                let categories = jsonData.map(function (item) {
                    return item.course_code;
                });

                let options = {
                    chart: {
                        type: 'bar',
                        height: 400,
                        width: 750,
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '50%',
                            borderRadius: 10,
                            borderRadiusApplication: 'end',
                            dataLabels: {
                                position: 'top', // top, center, bottom
                            },
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        offsetY: -30,
                        style: {
                            fontSize: '12px',
                            colors: ["#304758"]
                        }
                    },
                    legend: {
                        show: false
                    },
                    series: [{
                        name: 'Usuarios con certificado',
                        data: seriesData,
                    }],
                    xaxis: {
                        categories: categories,
                        labels: {
                            show: true,
                            style: {
                                fontSize: '12px'
                            }
                        }
                    },
                    toolbar: {
                        show: false // Esto oculta la barra de herramientas
                    }
                };

                chartCertificates = new ApexCharts(document.querySelector("#user_certificates_course"), options);
                chartCertificates.render();

            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Error: " + errorThrown);
            }
        });

        $.ajax({
            type: "POST",
            url: urlParticipants,
            data: data,
            dataType: "json",
            success: function(response) {
                let jsonData = response;
                let seriesData = jsonData.map(function (item) {
                    return item.participants;
                });
                let categories = jsonData.map(function (item) {
                    return item.course_name;
                });

                var options = {
                    series: [{
                        name: 'Usuarios participantes',
                        data: seriesData,
                    }],
                    chart: {
                        type: 'bar',
                        height: 400,
                        width: 750
                    },
                    plotOptions: {
                        bar: {
                            columnHeights: '50%',
                            distributed: true,
                            borderRadius: 10,
                            borderRadiusApplication: 'end',
                            horizontal: true,
                            dataLabels: {
                                position: 'top', // top, center, bottom
                            },
                        }
                    },
                    legend: {
                        show: false
                    },
                    dataLabels: {
                        enabled: true,
                        offsetX: 30,
                        style: {
                            fontSize: '12px',
                            colors: ["#304758"]
                        }
                    },
                    xaxis: {
                        categories: categories,
                        labels: {
                            show: true,
                            style: {
                                fontSize: '12px'
                            }
                        }
                    }
                };

                chartParticipants = new ApexCharts(document.querySelector("#user_participants_course"), options);
                chartParticipants.render();

            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Error: " + errorThrown);
            }
        });

        $.ajax({
            type: "POST",
            url: urlSession,
            data: data,
            dataType: "json",
            success: function(response) {
                let jsonData = response;
                let seriesData = jsonData.map(function (item) {
                    return item.nbr_users;
                });
                let categories = jsonData.map(function (item) {
                    return item.course_code;
                });

                let options = {
                    chart: {
                        type: 'bar',
                        height: 400,
                        width: 750,
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '50%',
                            distributed: true,
                            borderRadius: 10,
                            borderRadiusApplication: 'end',
                            dataLabels: {
                                position: 'top', // top, center, bottom
                            },
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        offsetY: -30,
                        style: {
                            fontSize: '12px',
                            colors: ["#304758"]
                        }
                    },
                    legend: {
                        show: false
                    },
                    series: [{
                        name: 'Usuarios inscritos',
                        data: seriesData,
                    }],
                    xaxis: {
                        categories: categories,
                        labels: {
                            show: true,
                            style: {
                                fontSize: '12px'
                            }
                        }
                    }
                };

                chartSession = new ApexCharts(document.querySelector("#user_register_course"), options);
                chartSession.render();

            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Error: " + errorThrown);
            }
        });

        $.ajax({
            type: "POST",
            url: urlStudent,
            data: data,
            dataType: "json",
            success: function(response) {

                circle_approved = new CircleProgress('#circle_approved', {
                    max: response.total,
                    value: response.totalApproved,
                    textFormat: function(value, max) {
                        return value;
                    }
                });

                circle_disapproved = new CircleProgress('#circle_disapproved', {
                    max: response.total,
                    value: response.totalDisapproved,
                    textFormat: function(value, max) {
                        return value;
                    }
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Error: " + errorThrown);
            }
        });
    }

</script>