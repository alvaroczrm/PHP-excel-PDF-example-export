 public function exportGastosSubproduct(Request $request, Response $response, array $args)
    {

        try {
            $params = $request->getQueryParams();

            $nombreNegocio = $this->generalRepository->getNameBusiness($params["idBusiness"]);

            $fechaDesde = date('d-m-Y', $params["datefrom"]);

            $fechaHasta = date('d-m-Y', $params["dateto"]);

            $titulo = 'Subproductos - Gastos por Subproductos';

            $json = $this->subproductRepository->getGastosSubproduct(0, $params["idTpv"], $params["idBusiness"], $params["datefrom"], $params["dateto"], $params["idSubproduct"], 0,$params["idFilter"]);

            $json = $json->result["gastosSubproduct"];


            if ($args["tipo"] == "PDF") {
                $filename = "gastos-por-materias-primas.pdf";

                $plantilla = file_get_contents(__DIR__ . "/../../TemplatesHTML/templatePDF.html");
                $plantilla = str_replace("[TITULO]", $titulo, $plantilla);
                $plantilla = str_replace("[NOMBRENEGOCIO]", $nombreNegocio, $plantilla);
                $plantilla = str_replace("[FECHADESDE]", $fechaDesde, $plantilla);
                $plantilla = str_replace("[FECHAHASTA]", $fechaHasta, $plantilla);


                $html = '<table style="width: 100%; font-family:\'Trebuchet MS\', Arial, Helvetica, sans-serif; color:#1e2225;">';
                $html .= '<thead>';
                $html .= '<tr style="background: #212529; color: #fff;">';
                $html .= '<td><strong>PROVEEDOR</strong></td>';
                $html .= '<td><strong>ETIQUETA</strong></td>';
                $html .= '<td><strong>CASA</strong></td>';
                $html .= '<td><strong>MATERIA PRIMA</strong></td>';
                $html .= '<td><strong>UNIDADES</strong></td>';
                $html .= '<td><strong>PARTES</strong></td>';
                $html .= '<td><strong>BASE</strong></td>';
                $html .= '<td><strong>IMPORTE con IVA</strong></td>';
                $html .= '<td><strong>PRECIO MED.</strong></td>';
                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody>';

                $xtotalPrecio = 0;
                $xtotalBase = 0;



                for ($i = 0; $i < count($json); $i++) {
                    
                    $xtotalPrecio += $json[$i]["total"];
                    $xtotalBase += $json[$i]["totalSinIva"];


                    $html .= '<tr>';

                    if ($json[$i]["nameSupplier"]) {

                        $html .= '<td>' . $json[$i]["nameSupplier"] . '</td>';
                    } else {

                        $html .= '<td> Sin Proveedor</td>';
                    }


                    if ($json[$i]["nameTag"]) {

                        $html .= '<td>' . $json[$i]["nameTag"] . '</td>';
                    } else {

                        $html .= '<td> Sin Categoría</td>';
                    }

                    if ($json[$i]["nameCasa"]) {

                        $html .= '<td>' . $json[$i]["nameCasa"] . '</td>';
                    } else {

                        $html .= '<td> Sin Casa</td>';
                    }

                    $costeTotal = $json[$i]["coste"] * $json[$i]["ud"];


                    $html .= '<td>' . $json[$i]["name"] . '</td>';

                    $html .= '<td style = "text-align: right">' . $json[$i]["ud"] . ' ' . $json[$i]["type"] . '</td>';
                    $html .= '<td style = "text-align: right">' . $json[$i]["partes"] . '</td>';

                    $html .= '<td style = "text-align: right" >' . number_format($json[$i]["totalSinIva"], 2, ',', '.') . ' € </td >';
                    $html .= '<td style = "text-align: right" >' . number_format($json[$i]["total"], 2, ',', '.') . ' € </td >';

                    if ($json[$i]["ud"] > 0) {
                        $html .= '<td style = "text-align: right" >' . number_format($json[$i]["coste"], 2, ',', '.') . ' € </td >';
                    } else {
                        $html .= '<td style = "text-align: right" > 0€ </td >';
                    }

                    $html .= '</tr >';
                }

                $html .= '<tfoot >';
                $html .= '<tr style="background: #212529; color: #fff;">';
                $html .= '<td><strong>Total:</strong></td>';
                $html .= '<td></td>';
                $html .= '<td></td>';
                $html .= '<td></td>';
                $html .= '<td></td>';
                // $html .= '<td style = "text-align: right" ><strong>' . $xtotalUds . ' / ' . $xtotalPartes . '</strong></td >';
                $html .= '<td></td>';
                $html .= '<td style = "text-align: right" ><strong>' . number_format($xtotalBase, 2, ',', '.') . ' € </strong></td >';
                $html .= '<td style = "text-align: right" ><strong>' . number_format($xtotalPrecio, 2, ',', '.') . ' € </strong></td >';
                $html .= '<td></td>';
                $html .= '</tr >';
                $html .= '</tfoot >';
                $html .= '</table >';

                $plantilla = str_replace("[HTML]", $html, $plantilla);

                $options = new Options();
                $options->set('tempDir', __DIR__ . '/site_uploads/dompdf_temp');
                $options->set('isRemoteEnabled', TRUE);
                $options->set('isHtml5ParserEnabled', true);

                $dompdf = new Dompdf($options);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->loadHtml($plantilla);
                $dompdf->render();
                $dompdf->stream($filename);
                die();

                /*$newResponse = $response->withHeader('Content-type', 'application/octet-stream')
                    ->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Disposition', 'attachment; filename=' . basename($filename))
                    ->withHeader('Content-Transfer-Encoding', 'binary')
                    ->withHeader('Expires', '0')
                    ->withHeader('Cache-Control', 'must-revalidate')
                    ->withHeader('Pragma', 'public')
                    ->withHeader('Content-Length', filesize($filename))
                    ->withBody($dompdf->stream($filename));*/


            } else {
                $filename = "gastos-por-materias-primas.xlsx";
                $fileTemporalExcel = __DIR__ . "/../../TemporalExcel/" . time() . ".xlsx";

                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                $sheet->setCellValue('A1', $titulo);
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER_CONTINUOUS);


                $sheet->setCellValue('A2', $nombreNegocio);
                $sheet->mergeCells('A2:G2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER_CONTINUOUS);
                $sheet->setCellValue('H2', $fechaDesde);
                $sheet->setCellValue('I2', $fechaHasta);

                $sheet->setCellValue('A3', 'PROVEEDOR');
                $sheet->setCellValue('B3', 'ETIQUETA');
                $sheet->setCellValue('C3', 'CASA');
                $sheet->setCellValue('D3', 'MATERIA PRIMA');
                $sheet->setCellValue('E3', 'UNIDADES');
                $sheet->setCellValue('F3', 'TIPO');
                $sheet->setCellValue('G3', 'PARTES');
                $sheet->setCellValue('H3', 'BASE');
                $sheet->setCellValue('I3', 'IMPORTE CON IVA');
                $sheet->setCellValue('J3', 'PRECIO MED.');


                $sheet->getColumnDimension('A')->setAutoSize(true);
                $sheet->getColumnDimension('B')->setAutoSize(true);
                $sheet->getColumnDimension('C')->setAutoSize(true);
                $sheet->getColumnDimension('D')->setAutoSize(true);
                $sheet->getColumnDimension('E')->setAutoSize(true);
                $sheet->getColumnDimension('F')->setAutoSize(true);
                $sheet->getColumnDimension('G')->setAutoSize(true);
                $sheet->getColumnDimension('H')->setAutoSize(true);
                $sheet->getColumnDimension('I')->setAutoSize(true);
                $sheet->getColumnDimension('J')->setAutoSize(true);

                $sheet->getStyle("A1:J1")->getFont()->setBold(true);
                $sheet->getStyle("A2:J2")->getFont()->setBold(true);
                $sheet->getStyle("A3:J3")->getFont()->setBold(true);


                $xtotalPrecio = 0;
                $xtotalBase = 0;

                $cell = 4;

                for ($i = 0; $i < count($json); $i++) {

                    $xtotalPrecio += $json[$i]["total"];
                    $xtotalBase += $json[$i]["totalSinIva"];

                    if ($json[$i]["nameSupplier"]) {
                        $sheet->setCellValue('A' . ($cell), $json[$i]["nameSupplier"]);
                    } else {
                        $sheet->setCellValue('A' . ($cell), 'Sin Proveedor');
                    }


                    if ($json[$i]["nameTag"]) {
                        $sheet->setCellValue('B' . ($cell), $json[$i]["nameTag"]);
                    } else {
                        $sheet->setCellValue('B' . ($cell), 'Sin Categoría');
                    }

                    if ($json[$i]["nameCasa"]) {
                        $sheet->setCellValue('C' . ($cell), $json[$i]["nameCasa"]);
                    } else {
                        $sheet->setCellValue('C' . ($cell), 'Sin Casa');
                    }

                    $costeTotal = $json[$i]["coste"] * $json[$i]["ud"];


                    $sheet->setCellValue('D' . ($cell), $json[$i]["name"]);

                    $sheet->setCellValue('E' . ($cell), $json[$i]["ud"]);
                    $sheet->setCellValue('F' . ($cell), $json[$i]["type"] );

                    $sheet->setCellValue('G' . ($cell), $json[$i]["partes"]);

                    $sheet->getCell('H' . $cell)
                        ->setValueExplicit(
                            $json[$i]["totalSinIva"],
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                        );// previamente -> $costeTotal/(1+$json[$i]["iva"]/100)

                    $sheet->getCell('I' . $cell)
                        ->setValueExplicit(
                            $json[$i]["total"],
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                        );

                    $sheet->getCell('J' . $cell)
                        ->setValueExplicit(
                            $json[$i]["coste"],
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                        );

                    $sheet->getStyle('H' . $cell)->getNumberFormat()->setFormatCode('0.00');
                    $sheet->getStyle('I' . $cell)->getNumberFormat()->setFormatCode('0.00');
                    $sheet->getStyle('J' . $cell)->getNumberFormat()->setFormatCode('0.00');

                    $cell++;

                }

                $sheet->setCellValue('A' . $cell, 'TOTAL');

                $sheet->getStyle('A' . $cell)->getFont()->setBold(true);

                $sheet->getCell('H' . $cell)
                    ->setValueExplicit(
                        $xtotalBase,
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                    );
                $sheet->getCell('I' . $cell)
                    ->setValueExplicit(
                        $xtotalPrecio,
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                    );

                $sheet->getStyle('H' . $cell)->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle('I' . $cell)->getNumberFormat()->setFormatCode('0.00');

                $writer = new Xlsx($spreadsheet);
                $writer->save($fileTemporalExcel);

                $fh = fopen($fileTemporalExcel, 'rb');
                $stream = new \Slim\Http\Stream($fh);

                $newResponse = $response->withHeader('Content-type', 'application/octet-stream')
                    ->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Disposition', 'attachment; filename=' . basename($filename))
                    ->withHeader('Content-Transfer-Encoding', 'binary')
                   ->withHeader('Expires', '0')
                    ->withHeader('Cache-Control', 'must-revalidate')
                    ->withHeader('Pragma', 'public')
                    ->withHeader('Content-Length', filesize($fileTemporalExcel))
                    ->withBody($stream);

                unlink($fileTemporalExcel);

                return ($newResponse);


            }


        } catch (\Exception $ex) {
            echo $ex;
            $this->logger->error($ex);
            return $response->withJson($ex);
        }
    }
