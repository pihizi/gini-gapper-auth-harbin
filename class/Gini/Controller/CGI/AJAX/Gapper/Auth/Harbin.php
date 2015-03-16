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

    /*
     * 这部分代码需要在 harbin_lab_orders 实现
     * class/Gini/Controller/CGI/AJAX/Layout/Header.php
    namespace Gini\Controller\CGI\AJAX\Layout;

    class Header extends \Gini\Controller\CGI
    {
        public function __index()
        {
            $me = _G('ME');
            if ($me->id) {
                $cart_navbar = \Gini\CGI::request('ajax/cart/navbar', $this->env)->execute()->content();
                $cart_brief = \Gini\CGI::request('ajax/cart/brief', $this->env)->execute()->content();
            }

            $vars = [
                'route' => $this->env['route'],
                'form' => $this->form(),
                'cart_navbar' => $cart_navbar,
                'cart_brief' => $cart_brief,
                // 实现扫二维码添加用户的功能
                // 最简单的实现是手机扫描二维码，拿到一个号，这个号查找库中对应的数据，自动填充
                'extend_menu' => (string)V('layout/harbin-extend-menu'),
            ];

            return \Gini\IoC::construct('\Gini\CGI\Response\HTML', V('layout/header', $vars));
        }
    }
     * 需要在 harbin_lab_orders 增加 qrcode的orm，记录qrcode对应的用户信息
    namespacd Gini\ORM;

    class QRCode extends Object
    {
        public $code    = 'string:250';
        public $ctime   = 'datetime';
        // public $info
    }
     */

    public function actionRegister()
    {
        // TODO 
        $data = []; // user posted data
        $code = 'asdfasdf.dfdf.df.sdfasdf'; // 对data产生的hash_hmac
        // 
        /*
        $qrcode = a('qrcode', ['qrcode'=>$code]);
        if (!$qrcode->id) {
            $qrcode->info = $data;
            $qrcode->save();
        }
         */
        $_SESSION[self::$sessionKey] = $code;
        return $this->showJSON([
            'type'=> 'modal',
            'message'=> (string) V('gapper/auth/harbin/confirm', [
            ])
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
