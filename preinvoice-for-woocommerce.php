<?php
/*
Plugin Name: پیشفاکتور ووکامرس
Description: Add a 'Download Invoice' button in WooCommerce Cart.
Version: 1.0
Author: Saeed Ghourbanian
*/

// if (!defined('ABS_PATH')) {
//     exit; // Exit if accessed directly
// }

// Add a custom button after the cart table
add_action('woocommerce_proceed_to_checkout', 'add_custom_invoice_button');
function add_custom_invoice_button()
{

?>
    <!-- Adding a spinner element -->
    <script>
        jQuery(document).ready(function($) {
            $('.generate-pdf-invoice').on('click', function(e) {
                e.preventDefault();

                // Disable the button and show spinner
                $(this).prop('disabled', true);
                $('.generate-pdf-spinner').show();

                $.ajax({
                    type: 'POST',
                    url: "<?= admin_url('admin-ajax.php') ?>", // WordPress AJAX URL
                    data: {
                        action: 'generate_pdf_invoice',
                        nonce: "<?= wp_create_nonce('pdfinvoice') ?>",
                        name: $('#name').val(),
                        id: $('#id').val(),
                        postcode: $('#postcode').val(),
                        phone: $('#phone').val(),
                        address: $('#address').val()
                    },
                    success: function(response) {
                        // Convert base64-encoded content to Blob
                        var byteCharacters = atob(response);
                        var byteNumbers = new Array(byteCharacters.length);
                        for (var i = 0; i < byteCharacters.length; i++) {
                            byteNumbers[i] = byteCharacters.charCodeAt(i);
                        }
                        var byteArray = new Uint8Array(byteNumbers);
                        var pdfBlob = new Blob([byteArray], {
                            type: 'application/pdf'
                        });

                        // Create a temporary anchor element to trigger download
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(pdfBlob);
                        link.download = 'invoice.pdf';
                        link.click();

                        // Re-enable the button and hide the spinner
                        $('.generate-pdf-invoice').prop('disabled', false);
                        $('.generate-pdf-spinner').hide();
                        $('#myModal').css('display', 'none');
                    },
                    error: function() {
                        // Re-enable the button and hide the spinner in case of an error
                        $('.generate-pdf-invoice').prop('disabled', false);
                        $('.generate-pdf-spinner').hide();
                    }
                });
            });
            // Open the modal when the button is clicked
            $('#openModal').on('click', function() {
                $('#myModal').css('display', 'block');
            });
            $('.close').on('click', function() {
                $('#myModal').css('display', 'none');
            });

        });
    </script>
    <style>
        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }

        .spinner:before {
            content: '';
            box-sizing: border-box;
            position: absolute;
            width: 20px;
            height: 20px;
            margin-right: 10px;
            margin-top: -10px;
            border-radius: 50%;
            border-top: 2px solid #fff;
            border-right: 2px solid transparent;
            animation: spinner .6s linear infinite;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            text-align: center;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
    <button id="openModal" class="checkout-button">دریافت پیشفاکتور</button>

    <div id="myModal" class="modal">
        <div class="modal-content">

            <span class="close">&times;</span>
            <h2>لطفا مشخصات درخواست دهنده تسهیلات را وارد کنید</h2>

            <div style="display: flex;justify-content: space-between;text-align: right;margin-bottom: 25px;">
                <div>
                    <label for="name">نام و نام خانوادگی:</label>
                    <input type="text" id="name" name="name">
                </div>
                <div>
                    <label for="id">کد ملی:</label>
                    <input type="text" id="id" name="id">
                </div>
                <div>
                    <label for="phone">شماره تماس:</label>
                    <input type="text" id="phone" name="phone">
                </div>
            </div>
            <div style="display: flex;justify-content: space-between;text-align: right;margin-bottom: 25px;">
                <div style="width: 100%;margin-left: 15px;">
                    <label for="address">نشانی:</label>
                    <input type="text" id="address" name="address">
                </div>
                <div>
                    <label for="postcode">کد پستی: </label>
                    <input type="text" id="postcode" name="postcode">

                </div>
            </div>

            <p>
                <a href="#" class="button alt generate-pdf-invoice checkout-button" style="background: #30c549;">دریافت پیشفاکتور<span class="spinner generate-pdf-spinner" style="display: none;"></span></a>
            </p>
        </div>
    </div>


<?php
}



// Create an AJAX endpoint to handle the PDF generation
add_action('wp_ajax_generate_pdf_invoice', 'generate_pdf_invoice_ajax');
add_action('wp_ajax_nopriv_generate_pdf_invoice', 'generate_pdf_invoice_ajax'); // For non-logged in users

function generate_pdf_invoice_ajax()
{
    if (!wp_verify_nonce($_POST['nonce'], 'pdfinvoice')) {
        wp_die('Security check error!');
    }

    // Load WooCommerce
    if (!class_exists('WooCommerce')) {
        include_once WC_ABSPATH . 'includes/wc-core-functions.php';
        include_once WC_ABSPATH . 'includes/class-wc-cart.php';
        include_once WC_ABSPATH . 'includes/class-wc-checkout.php';
    }

    // require_once(get_stylesheet_directory() . 'vendor/autoload.php'); // Path to mPDF autoload file
    require_once __DIR__ . '/vendor/autoload.php';

    $formatter = new IntlDateFormatter(
        "fa_IR@calendar=persian",
        IntlDateFormatter::FULL,
        IntlDateFormatter::FULL,
        'Asia/Tehran',
        IntlDateFormatter::TRADITIONAL,
        "yyyy/MM/dd"
    );
    $now = new DateTime();

    $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];

    $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',

        'fontDir' => array_merge($fontDirs, [__DIR__]),
        'fontdata' => $fontData +
            [
                'vazir' => [
                    'R' => 'assets/Vazirmatn-Regular.ttf',
                ],
                'inkfree' => [
                    'R' => 'assets/Vazirmatn-Bold.ttf',
                ],
            ],
    ]);
    $mpdf->SetDisplayMode('fullpage');


    ob_clean();
    $html = '
    <!DOCTYPE html>
<html lang="fa">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        table {
            direction: rtl;
            width: 100%;
        }
        th, td {
            padding: 8px;
            text-align: right;
            font-size: 10px;
            font-weight: 500;
            line-height: 16px;
            
        }
        th{
            padding: 10px;
            text-align: right;
            font-size: 10px;
            font-weight: 500;
            line-height: 16px;
        }
        .seller {
            display: table;
            padding: 8px;
            background: #E3E3E3;
            margin: 0;
            width: 100%;
            font-size: 10px;
            font-weight: 500;
            line-height: 16px;
            text-align: right;
        }
        .info-table td{
            border:0.1mm solid #3D3D3D;
            border-collapse: collapse !important; 
            border-spacing: 0px;
        }
    </style>
</head>
    <body>
    
    <table style="direction: rtl;" autosize="2.4">
    <tr>
    
        <th style="font-size:14px;"><img style="vertical-align: top" src="' . __DIR__ . '/assets/alpha.png" width="129" /></th>    
        <th style="font-size:14px;" >پیش فاکتور فروش کالا و خدمات</th>
        <th style="direction:rtl;font-size: 12px;font-weight: 400;line-height: 19px;letter-spacing: 0em;text-align: right;" >تاریخ: ' . $formatter->format($now) . '</th>
    </tr>
    </table>

    <table>
    <tr class="seller" style="display: table; padding: 8px;background: #E3E3E3;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;">
        <td>فروشنده:</td>
        
    </tr>
    </table>
    <table style="background:#F3F3F3;">
    <tr>
        <td>نام شخص حقیقی/حقوقی: مهر آوران بازار نوین</td>
        <td>شماره ثبت: 570695</td>
        <td>شناسه اقتصادی: 14009694200</td>
        <td>شناسه ملی: 14009694200</td>
    </tr>
        <tr>
            <td>تلفن: 021-22957274</td>
            <td>کد پستی: 1667716365</td>
            <td colspan="2">نشانی: تهران میدان هروی بلوار پناهی نیا بین دهم و دوازدهم پلاک ۷۷ واحد ۲ </td>
        </tr>
        </table>
        <table>
    <tr class="seller" style="display: table; padding: 0px;border:0;background: #E3E3E3;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;">
        <td colspan="4">خریدار:</td>
    </tr>
    </table>
    <table style="background:#F3F3F3;margin:0;padding:0;">
        <tr>
            <td colspan="2">نام شخص حقیقی/حقوقی:' . $_POST['name'] . '</td>
        <td>کد ملی: ' . $_POST['id'] . '</td>
        <td colspan="2">کد پستی: ' . $_POST['postcode'] . '</td>
        <td colspan="2">شماره تماس: ' . $_POST['phone'] . '        </td>
        <tr>
            
        <td>نشانی: ' . $_POST['address'] . '</td>
        </tr>
        </table>
        <table>
        <tr class="seller" style="display: table; padding: 8px;background: #E3E3E3;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;">
            <th colspan="4">مشخصات کالا یا خدمات موردمعامله:</th>
        </tr>
        </table>
        <table>
        <tr class="seller" style="display: table; padding: 8px;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;"> 
            <th>ردیف</th>
            <th colspan="2">کالا</th>
            <th>تعداد</th>
            <th colspan="2">قیمت فی (تومان)</th>
        </tr>';

    // Get WooCommerce cart content
    $cart = WC()->cart->get_cart();
    $total_sum = 0;


    $i = 1;

    foreach ($cart as $item_key => $item) {
        $product = $item['data'];
        $product_name = $product->get_name();
        $product_price = $product->get_price(); // Multiply price by 2
        $quantity = $item['quantity'];
        $total_sum += $product_price * $quantity;
        $html .=    '<tr class="info-table">
                    <td>' . $i . '</td>
                    <td colspan="2">' . $product_name . '</td>
                    <td>' . $quantity . '</td>
                    <td colspan="2">' . number_format($product_price) . '</td>
                </tr>';

        $i++;
    }
    $total_sum = number_format($total_sum);

    $html .= '
    </table>
        <table>
     <tr class="seller" style="display: table; padding: 8px;background: #E3E3E3;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;">
            <th colspan="3">مجموع: </th>
            <th colspan="3" >' . $total_sum . ' تومان </th>
        </tr>
    </table>
    <p style="direction:rtl; text-align:right;">اعتبار این پیشفاکتور ۲۴ ساعت بوده و فروشگاه هیچ گونه مسئولیتی بابت تغییر قیمت کالا پس از آن ندارد.</p>
    </body>

    </html>';
    // wp_die($html);
    $mpdf->WriteHTML($html);
    ob_clean();
    // Output the PDF as a download
    $pdf_content = $mpdf->Output('invoice.pdf', \Mpdf\Output\Destination::STRING_RETURN);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="invoice.pdf"');
    echo base64_encode($pdf_content);
    exit;
}
