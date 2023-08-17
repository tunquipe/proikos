
    <table style="border: 2px solid #000; width: 850px; border-collapse: collapse;">
        <tr style="border: 1px solid #000;">
            <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase; font-weight: bold; height: 40px;">
                Nº
            </td>
            <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase; width: 40%; font-weight: bold;">
                Apellidos y Nombres
            </td>
            <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase; font-weight: bold;">
                DNI Nº
            </td>
            <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase; font-weight: bold;">
                Ficha Nº
            </td>
            <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase;font-weight: bold;">
                Dependencia
            </td>
            <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase;font-weight: bold;">
                Firma
            </td>
            <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase;font-weight: bold;">
                Observaciones
            </td>
        </tr>
        {% for student in students %}
        <tr style="text-align: center; border: 1px solid #000;">
            <td style="border-right: 1px solid #000; height: 40px;">{{ student.number }}</td>
            <td style="border-right: 1px solid #000; text-transform: uppercase;">{{ student.lastname }
                }, {{ student.firstname }}
            </td>
            <td style="border-right: 1px solid #000;">{{ student.email }}</td>
            <td style="border-right: 1px solid #000;"></td>
            <td style="border-right: 1px solid #000;"></td>
            <td style="border-right: 1px solid #000;"></td>
            <td></td>
        </tr>
        {% endfor %}

    </table>
