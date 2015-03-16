<?php
/**
* @file Harbin.php
* @brief 哈尔滨登录注册模块
* @author Hongjie Zhu
* @version 0.1.0
* @date 2015-03-13
 */

namespace Gini\Controller\CGI\AJAX\Gapper\Auth;

class Harbin extends \Gini\Controller\CGI
{
    use \Gini\Module\Gapper\Client\RPCTrait;
    use \Gini\Module\Gapper\Client\CGITrait;
    use \Gini\Module\Gapper\Client\LoggerTrait;

    private static $sessionKey = 'harbin.register.qrcode';

    protected function getConfig()
    {
        $infos = (array)\Gini\Config::get('gapper.auth');
        return (object)$infos['harbin'];
    }

    public function actionPDF()
    {
        $pdf = new \TCPDF;
        $pdf->setTitle('title');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetFont('simfang', 'B', 20, APP_PATH . '/' . DATA_DIR . '/fonts/simfang');
        $pdf->AddPage();
        $pdf->writeHTMLCell(0, 0, 50, 20, 'T_T', 0, 1, 0, true, '', true);
        $data = $_SESSION[self::$sessionKey];
        $pdf->write2DBarcode($data, 'QRCODE,L', 150, 60, 40, 40, '', 'N');
        $pdf->Output('document.pdf', 'I');
    }

    public function actionQRCode()
    {
        $data = $_SESSION[self::$sessionKey];
        $code = new \TCPDF2DBarcode($data, 'QRCODE,L');
        header('Pragma: no-cache');
        header('Content-type: image/png');
        echo $code->getBarcodePNG(4, 4);
        exit;
    }

    public function actionRegister()
    {
        $form = $this->form('post');
        $secret = \Gini\Config::get('app.harbin_secret');
        // TODO
        $data = []; // user posted data
        $code = hash_hmac('sha1', json_encode($data), $secret);
        $qrcode = a('qrcode', ['qrcode'=>$code]);
        if (!$qrcode->id) {
            $qrcode->info = $data;
            $qrcode->save();
        }
        $_SESSION[self::$sessionKey] = $code;
        return $this->showJSON([
            'type'=> 'modal',
            'message'=> (string) V('gapper/auth/harbin/confirm', $data)
        ]);
    }

    /**
        * @brief 获取新增用户表单
        *
        * @return 
     */
    public function actionGetForm()
    {
        $config = $this->getConfig();
        return $this->showHTML('gapper/auth/harbin/register', [
            'icon'=> $config->icon,
            'type'=> $config->name
        ]);
    }
}
