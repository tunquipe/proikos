{{ form }}

<div class="row">
    <div class="col-md-4">
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
    <div class="col-md-8">
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
    </div>
</div>



<script type="text/javascript">

    document.addEventListener("DOMContentLoaded", function() {
        let starDate = $("#star_date").val();
        let endDate = $("#end_date").val();
        let urlCampus = '{{_p.web}}';
        let url = urlCampus + '/plugin/proikos/src/ajax.php?action=get_status_of_students&star_date='+starDate+'&end_date='+endDate;
        let counterText = $("#counter");
        $.getJSON(url, function(response) {
            console.log(response)
            new CircleProgress('#circle_approved', {
                max: 100,
                value: response.percentage_approved,
                textFormat: 'percent'
            });

            new CircleProgress('#circle_disapproved', {
                max: 100,
                value: response.percentage_disapproved,
                textFormat: 'percent'
            });

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
            $('#progress_bar .ui-progress').animateProgress(response.percentage_total_current, function() {
                $(this).animateProgress(response.percentage_total_current, function() {
                    setTimeout(function() {
                        $('#progress_bar .ui-progress').animateProgress(100, function() {
                            $('.content_success').slideDown();
                        });
                    }, 3000);
                });
            });

            $('.counter').each(function () {
                let $this = $(this),
                    countTo = Math.round(response.percentage_total_current);
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


        });
    });

</script>