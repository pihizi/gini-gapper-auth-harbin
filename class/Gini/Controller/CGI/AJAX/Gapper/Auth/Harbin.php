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

    private static function _setCodeRawData($data)
    {
        $_SESSION[self::$sessionKey] = $data;
    }

    private static function _getCode()
    {
        $data = $_SESSION[self::$sessionKey];
        $secret = \Gini\Config::get('app.harbin_secret');
        $code = hash_hmac('sha1', json_encode($data), $secret);
        return $code;
    }

    private static function _getQRCodeText()
    {
        $data = $_SESSION[self::$sessionKey];
        $code = self::_getCode();
        $ret = "唯一标识: {$code}";
        return $ret;
    }

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
        $data = self::_getQRCodeText();
        $pdf->write2DBarcode($data, 'QRCODE,L', 150, 60, 40, 40, '', 'N');
        $pdf->Output('document.pdf', 'I');
    }

    public function actionQRCode()
    {
        $data = self::_getQRCodeText();
        $code = new \TCPDF2DBarcode($data, 'QRCODE,L');
        header('Pragma: no-cache');
        header('Content-type: image/png');
        echo $code->getBarcodePNG(4, 4);
        exit;
    }

    private static function _validate($key, $value)
    {
        switch ($key) {
        case 'wid':
            if (!strlen($value)) {
                return T("请输入PI工号!");
            }
            break;
        case 'name':
            if (!strlen($value)) {
                return T("请输入PI姓名!");
            }
            break;
        case 'email':
            $pattern = '/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/';
            if (!preg_match($pattern, $value)) {
                return T('请使用正确的Email!');
            }
            break;
        case 'department':
            if (!strlen($value)) {
                return T('请填写院系名称!');
            }
            break;
        case 'group':
            if (!strlen($value)) {
                return T('请填写课题组名称!');
            }
            break;
        }
    }

    public function actionRegister()
    {
        $form = $this->form('post');
        $data = [
            'wid'=> trim($form['wid']),
            'name'=> trim($form['name']),
            'department'=> trim($form['department']),
            'group'=> trim($form['group']),
            'email'=> trim($form['email']),
            'phone'=> trim($form['phone']),
            'address'=> trim($form['address'])
        ]; 

        $error = [];
        foreach ($data as $k=>$v) {
            $r = self::_validate($k, $v);
            if ($r) {
                $error[$k] = $r;
            }
        }

        if (!empty($error)) {
            return $this->showJSON([
                'type'=> 'modal',
                'message'=> (string) V('gapper/auth/harbin/register', $data + ['error'=>$error])
            ]);
        }

        self::_setCodeRawData($data);
        $code = self::_getCode();
        $qrcode = a('qrcode', ['qrcode'=>$code]);
        if (!$qrcode->id) {
            foreach ($data as $k=>$v) {
                $qrcode->$k = $v;
            }
            $qrcode->save();
        }
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
        return $this->showHTML('gapper/auth/harbin/register', []);
    }
}
