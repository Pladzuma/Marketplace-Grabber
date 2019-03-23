<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Парсер Amazon</title>
    <style>
    table {
        border-collapse:collapse;
    }
    table td, table th {
        border:1px solid grey;
    }
    .table-wrap {
        max-height:650px;
        max-width:100vw;
        overflow:auto;
    }
    </style>
</head>
<body>
<?php

/// BEGIN ____________________________________________________________ >>>

    $start = microtime(true);

    function parseCat($parsepage_count,$parsepage_url) { // параметры: кол-во стр, ссылка на страницу

        require_once('libs/phpQuery.php');
        require_once('libs/phpexcel/PHPExcel.php');

        // -------------------------------------------------- \\

        $phpexcel = new PHPExcel();
        $page = $phpexcel->setActiveSheetIndex(0);
        $page->setTitle("Парсим amazon");

        $page->setCellValue("A1", 'Подкатегория');
        $page->setCellValue("B1", 'Title');
        $page->setCellValue("C1", 'Asin');
        $page->setCellValue("D1", 'URL');
        $page->setCellValue("E1", 'Цена');
        $page->setCellValue("F1", 'Первый буллит');

        // -------------------------------------------------- \\

        echo '
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Адрес ссылки</th>
                    <th>Подкатегория</th>
                    <th>Title</th>
                    <th>Asin</th>
                    <th>Цена</th>
                    <th>1-ый буллит</th>
                </tr>
            </thead>
            <tbody>
             ';

        $curl_req = curl_init(); // инициализация сеанса curl

        for ($pg_it = 0; $pg_it < $parsepage_count; $pg_it++) {

            curl_setopt($curl_req, CURLOPT_URL, $parsepage_url.'&page='.$pg_it); // задаём url страницы товаров

            curl_setopt($curl_req, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)'); // задаём юзерагент переменную/указываем устройство 
            curl_setopt($curl_req, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl_req, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($curl_req, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt ($curl_req, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl_req, CURLOPT_ENCODING, 'gzip');
            $curl_res = curl_exec($curl_req);

            $pq = phpQuery::newDocument($curl_res);
            $products = $pq->find('.s-result-item');
            $it_numb = 24 * $pg_it + 1 ; // задаём номер строки для excel (хитрая задумка :) )

            foreach ($products as $product) {
                $it_numb++; //

                $pq_product = pq($product);
                $prod_html = $pq_product->htmlOuter();
                $prod_asin = $pq_product->attr('data-asin');
                $prod_title = $pq_product->find('h2');
                $prod_link = $pq_product->find('a.s-access-detail-page');
                $prod_address = $prod_link->attr('href');

                echo '<tr><td>'.$prod_address.'</td>';

                $curl_req_in = curl_init();

                curl_setopt($curl_req_in, CURLOPT_URL, $prod_address);
                curl_setopt($curl_req_in, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)');
                curl_setopt($curl_req_in, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($curl_req_in, CURLOPT_RETURNTRANSFER,1);
                curl_setopt ($curl_req_in, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt ($curl_req_in, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl_req_in, CURLOPT_ENCODING, 'gzip');

                $curl_res_in = curl_exec($curl_req_in);

                $pq_in = phpQuery::newDocument($curl_res_in);

                $prodin_subcat = $pq_in->find('#wayfinding-breadcrumbs_feature_div > ul > li:last')->text(); // поиск подкатегории
                $prodin_title = $pq_in->find('#productTitle')->text(); // поиск title
                $prodin_info = $pq_in->find('#cerberus-data-metrics'); // получение блока значений
                $prodin_asin = $prodin_info->attr('data-asin'); // поиск asin
                $prodin_price = $prodin_info->attr('data-asin-price'); // поиск цены
                $prodin_bullet1 = $pq_in->find('#feature-bullets > ul > li:first')->text(); // поиск первого    буллета

                echo '
                    <td>'.$prodin_subcat.'</td>
                    <td>'.$prodin_title.'</td>
                    <td>'.$prodin_asin.'</td>
                    <td>'.$prodin_price.'</td>
                    <td>'.$prodin_bullet1.'</td>
                </tr>
                     ';
            // -------------------------------------------------- \\

                $page->setCellValue("A".$it_numb, $prodin_subcat);
                $page->setCellValue("B".$it_numb, $prodin_title);
                $page->setCellValue("C".$it_numb, $prodin_asin);
                $page->setCellValue("D".$it_numb, $prod_address);
                $page->setCellValue("E".$it_numb, $prodin_price);
                $page->setCellValue("F".$it_numb, $prodin_bullet1);
            }
        }

        $objWriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');
        $objWriter->save("parsed.xlsx");

        echo '
            </tbody>
        </table>
    </div>
             ';

    }

    parseCat(5,'https://www.amazon.com/s/browse/ref=nav_shopall-export_nav_mw_sbd_intl_pet?_encoding=UTF8&node=16225013011');

    echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';

 /// >>> ____________________________________________________________ END

?>
</body>
</html>