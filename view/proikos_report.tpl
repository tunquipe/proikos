{{ form }}
<style>
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
    #exams {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }
    .chart-item {
        flex: 1;
        max-width: 300px;
        margin: 10px;
        min-height: 217.7px;
        background-color: #f2f2f2;
        border: 1px solid #ccc;
        padding: 10px;
        box-sizing: border-box;
    }
    #trained_workers{
        max-width: 750px;
        min-height: 200px;
        margin: 10px auto;
    }
</style>
<div class="bg-report">

    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">Trabajadores entrenados</h3>
                <div id="trained_workers"></div>
            </div>
        </div>
    </div>

    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">Evaluaciones realizadas por curso</h3>
                <div id="exams" class="row"></div>
            </div>
        </div>
    </div>

    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">Usuarios aprobados y desaprobados</h3>

                <div class="row flex-container">
                    <div class="col flex-item">
                        <img src="{{ url_plugin_image }}/man_and_woman.png">
                    </div>
                    <div class="col flex-item">
                        <div id="chart_approved_disapproved" class="circle-bar-progress">
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

    function capitalizeTitle(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function replaceSpaces(string) {
        return string.replace(/ /g, '-');
    }

    function createRandomColor() {
        // Genera un color aleatorio en formato hexadecimal
        return '#' + Math.floor(Math.random()*16777215).toString(16);
    }

    function loadData(data){

        let urlCampus = '{{_p.web}}';
        let chartSession;
        let chartParticipants;
        let chartCertificates;
        let chartApproved;
        let chartStakeholders ;
        let chart_approved_disapproved;
        let chart_total;

        let urlStudent = urlCampus + 'plugin/proikos/src/ajax.php?action=get_students_approved_disapproved';
        let urlSession = urlCampus + 'plugin/proikos/src/ajax.php?action=get_report_session';
        let urlParticipants = urlCampus + 'plugin/proikos/src/ajax.php?action=get_participating_users';
        let urlCertificates = urlCampus + 'plugin/proikos/src/ajax.php?action=get_certificate_users';
        let urlApproved = urlCampus + 'plugin/proikos/src/ajax.php?action=get_course_approved';
        let urlStakeholders = urlCampus + 'plugin/proikos/src/ajax.php?action=get_participating_stakeholders';
        let urlExams = urlCampus + 'plugin/proikos/src/ajax.php?action=get_exams_students';
        let urlTotals = urlCampus + 'plugin/proikos/src/ajax.php?action=get_report_students';

        if (chart_total) {
            chart_total.destroy();
        }
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
        if (chart_approved_disapproved) {
            chart_approved_disapproved.destroy();
        }

        $.ajax({
            type: "POST",
            url: urlTotals,
            data: data,
            dataType: "json",
            success: function(response) {
                let options = {
                    series: [{
                        name: 'Entrenados',
                        data: [response.total_current]
                    }, {
                        name: 'Total registados en plataforma',
                        data: [response.total_global]
                    }],
                    chart: {
                        type: 'bar',
                        height: 200,
                        width: 750,
                        stacked: true,
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            dataLabels: {
                                total: {
                                    enabled: true,
                                    offsetX: 0,
                                    style: {
                                        fontSize: '13px',
                                        fontWeight: 900
                                    }
                                }
                            }
                        },
                    },
                    stroke: {
                        width: 1,
                        colors: ['#fff']
                    },
                    xaxis: {
                        categories: ['Trabajadores']
                    },
                    fill: {
                        opacity: 1
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left',
                        offsetX: 40
                    }
                };

                chart_total = new ApexCharts(document.querySelector("#trained_workers"), options);
                chart_total.render();

            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Error: " + errorThrown);
            }
        });





        $.ajax({
            type: "POST",
            url: urlExams,
            data: data,
            dataType: "json",
            success: function(response) {
                //console.log(response);
                var examsElement = document.getElementById('exams');
                while (examsElement.firstChild) {
                    examsElement.removeChild(examsElement.firstChild);
                }
                response.forEach(function(data) {
                    let chartData = {
                        series: [data.exam_taken, data.exam_not_taken],
                        chart: {
                            type: 'donut',
                        },
                        labels: ['Realizado', 'No Realizado'],
                        dataLabels: {
                            enabled: false
                        },
                        legend: {
                            position: 'bottom', // Mueve las etiquetas a la parte inferior
                        },
                        title: {
                            text: capitalizeTitle(data.title),
                            align: 'center',
                            margin: 10,
                        },
                        colors: ['#259ffb', '#fe6077'],
                        responsive: [{
                            breakpoint: 350,
                            options: {
                                chart: {
                                    width: 100
                                }
                            }
                        }]
                    };

                    var chartElement = document.createElement('div');
                    chartElement.id = replaceSpaces(data.title); // Asigna el id basado en el título del gráfico
                    chartElement.classList.add('chart-item');
                    chartElement.style.width = '250px'; // Ajusta el ancho del gráfico según tus necesidades
                    chartElement.style.margin = '5px'; // Ajusta el margen entre gráficos según tus necesidades

                    document.getElementById('exams').appendChild(chartElement);

                    var chart = new ApexCharts(chartElement, chartData);
                    chart.render();
                });

            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Error: " + errorThrown);
            }
        });

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


                let options = {
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


                let options = {
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

                let options = {
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

                let seriesDataApproved = response.totalApproved;
                let seriesDataDisapproved = response.totalDisapproved;

                let pieChartData = [seriesDataApproved, seriesDataDisapproved];

                let options = {
                    series: pieChartData,
                    chart: {
                        width: 380,
                        type: 'pie',
                    },
                    labels: ['Aprobados (' + response.totalApproved +')' , 'Desaprobados  (' + response.totalDisapproved +')'],
                    dataLabels: {
                        enabled: false
                    }
                };

                chart_approved_disapproved = new ApexCharts(document.querySelector("#chart_approved_disapproved"), options);
                chart_approved_disapproved.render();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Error: " + errorThrown);
            }
        });
    }

</script>