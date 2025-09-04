<style>
    /*#swowl-panel{
        position: absolute;
        right: 0;
        top: 190px;
        width: 300px;
    }*/
    #swowl-panel{
        text-align: center;
    }
    .top-header{
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: flex-start;
        padding: 10px 0;
    }
    .top-content h3{
        font-weight: bold;
        color: #b8e8ff;
        margin-top: 0;
        margin-bottom: 1rem;
    }
    .top-content{
        background-color: #009edf;
        padding: 10px;
        color: #FFF;
        border-radius: 10px;
    }
    #swowl-panel .btn-primary{
        background-color: #FFF;
        color: #009edf;
        font-weight: bold;
        border-color: #009edf;
        padding: 15px 25px;
        font-size: 16px;
        border-radius: 10px;
    }
    #swowl-panel .btn-primary:hover{
        background-color: #0083b6;
        color: #FFF;
    }
</style>
<div id="swowl-panel">
    <div class="card">
        <div class="card-body">
            <div class="top-header">
                <img src="{{ src_plugin }}/images/logo-smowl.png" style="margin: auto;" alt="" class="img-responsive" width="150px" >
            </div>
            <div class="top-content">
                <h3>SMOWL panel</h3>
                <p>Registrate o accede en Smowl para activar el seguimiento y poder acceder a tu evaluación</p>
                <a class="btn btn-primary" href="url_smowl" target="_blank">
                    Accede a tu examen mediante SMOWL
                </a>
            </div>
        </div>
    </div>
</div>
