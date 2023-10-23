{{ form }}
<style>
    #user_register_course{
        max-width: 650px;
        margin: 10px auto;
    }
    #user_participants_course{
        max-width: 750px;
        min-height: 400px;
        margin: 10px auto;
    }
</style>
<div class="bg-report">
    <div class="row">
        <div class="col-md-3">
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
        <div class="col-md-9">
            <div class="container-box text-center">
                <div class="row flex-container">
                    <div class="col flex-item">
                        <img src="{{ url_plugin_image }}/man_and_woman.png">
                    </div>
                    <div class="col flex-item">
                        <h3 class="title">Aprobados</h3>
                        <div id="circle_approved" class="circle-bar-progress">
                        </div>
                    </div>
                    <div class="col flex-item">
                        <h3 class="title">Desaprobados</h3>
                        <div id="circle_disapproved" class="circle-bar-progress">
                        </div>
                    </div>
                </div>
            </div>
            <div class="container-box">
                <h3 class="title">Usuarios inscritos por cada curso</h3>
                <div id="user_register_course"></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="container-box">
                <h3 class="title">Usuarios participantes por cada curso</h3>
                <div id="user_participants_course"></div>
            </div>
        </div>
    </div>
</div>




<script type="text/javascript">
    $("#report_stakeholders").change(function (){
        let idSelector = $("#report_stakeholders").val();
        console.log(idSelector);
        if(idSelector == 1 ){
            $('#option-builder').hide();
        } else {
            $('#option-builder').show();
        }
    });

    $(document).ready(function() {
        // Esperar a que el documento se cargue completamente
        let urlCampus = '{{_p.web}}';
        let chartSession;
        let chartParticipants;
        var circle_approved;
        var circle_disapproved;
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
                end_date: $("#end_date").val()
            };

            let urlStudent = urlCampus + 'plugin/proikos/src/ajax.php?action=get_report_students';
            let urlSession = urlCampus + 'plugin/proikos/src/ajax.php?action=get_report_session';
            let urlParticipants = urlCampus + 'plugin/proikos/src/ajax.php?action=get_participating_users';

            if (chartSession) {
                chartSession.destroy();
            }
            if (chartParticipants) {
                chartParticipants.destroy();
            }

            $.ajax({
                type: "POST",
                url: urlParticipants,
                data: data,
                dataType: "json",
                success: function(response) {
                    let jsonData = response;
                    let seriesData = jsonData.map(function (item) {
                        return item.evaluations;
                    });
                    let categories = jsonData.map(function (item) {
                        return item.course_name;
                    });

                    var options = {
                        series: [{
                            name: 'Cursos',
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
                            offsetX: -30,
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
                            height: 350,
                            width: 650,
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
                            name: 'Usuarios',
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
                    //console.log(response); // Aquí puedes manejar la respuesta del servidor
                    if(circle_approved){
                        circle_approved.update({
                            value: response.data.percentage_approved // Actualiza con los nuevos datos
                        });
                    } else {
                        circle_approved = new CircleProgress('#circle_approved', {
                            max: 100,
                            value: response.data.percentage_approved,
                            textFormat: 'percent'
                        });
                        circle_approved.updateComplete
                    }
                    if(circle_disapproved){
                        circle_approved.update({
                            value: response.data.percentage_disapproved // Actualiza con los nuevos datos
                        });
                    } else {
                        circle_disapproved = new CircleProgress('#circle_disapproved', {
                            max: 100,
                            value: response.data.percentage_disapproved,
                            textFormat: 'percent'
                        });
                    }



                    new CircleProgress('#exam_one', {
                        max: 100,
                        value: 35,
                        textFormat: 'percent'
                    });

                    new CircleProgress('#exam_two', {
                        max: 100,
                        value: 80,
                        textFormat: 'percent'
                    });

                    // Hide the label at start
                    $('#progress_bar .ui-progress .ui-label').hide();
                    // Set initial value
                    $('#progress_bar .ui-progress').css('width', '0%');

                    // Simulate some progress
                    $('#progress_bar .ui-progress').animateProgress(response.data.percentage_total_current, function() {
                        $(this).animateProgress(response.data.percentage_total_current, function() {
                            setTimeout(function() {
                                $('#progress_bar .ui-progress').animateProgress(100, function() {
                                    $('.content_success').slideDown();
                                });
                            }, 3000);
                        });
                    });

                    $('.counter').each(function () {
                        let $this = $(this),
                            countTo = Math.round(response.data.percentage_total_current);
                        $({countNum: $this.text()}).animate({
                                countNum: countTo
                            },
                            {
                                duration: 3000,
                                easing: 'linear',
                                step: function () {
                                    $this.text(Math.round(this.countNum)+'%');
                                },
                                complete: function () {
                                    $this.text(Math.round(this.countNum)+'%');
                                    //alert('finished');
                                }
                            });
                    });

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log("Error: " + errorThrown);
                }
            });

        });
    });

</script>