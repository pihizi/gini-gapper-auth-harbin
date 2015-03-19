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

    private static function _getCodeRawData()
    {
        return $_SESSION[self::$sessionKey];
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
        $ret = H(T('唯一标识:')) . $code;
        $ret .= H(T('学院名称:')) . $data['department'];
        $ret .= H(T('课题组名称:')) . $data['group'];
        $ret .= H(T('PI 姓名:')) . $data['name'];
        $ret .= H(T('PI 工号:')) . $data['wid'];
        $ret .= H(T('PI 邮箱:')) . $data['email'];
        $ret .= H(T('联系电话:')) . $data['phone'];
        $ret .= H(T('地址:')) . $data['address'];
        return $ret;
    }

    protected function getConfig()
    {
        $infos = (array)\Gini\Config::get('gapper.auth');
        return (object)$infos['harbin'];
    }

    public function actionPDF()
    {
        $title = T('天津科技大学采购平台课题组注册表');
        $pdf = new \TCPDF;
        $pdf->setTitle($title);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);

        $pdf->AddPage();

        $pdf->SetFont('simfang', 'B', 20, APP_PATH . '/' . DATA_DIR . '/fonts/simfang');
        $pdf->writeHTMLCell(0, 0, 50, 20, '<h1>' . H($title) . '</h1>', 0, 1, 0, true, '', true);
        $pdf->SetLineStyle([
            'width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 2, 'color' => [0, 0, 0]
        ]);
        $pdf->Line(10, 40, 200, 40, ['width'=>0.5]);

        $pdf->SetFont('simfang', '', 11, APP_PATH.'/'.DATA_DIR.'/fonts/simfang');
        $pdf->writeHTMLCell(
            145, 20, 30, 140, 
            V('gapper/auth/harbin/pdf.phtml', (array)self::_getCodeRawData()), 
            0, 1, 0, true, '', true);

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
                return T('请填写学院信息!');
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
        $qrcode = a('qrcode', ['code'=>$code]);
        if (!$qrcode->id) {
            $qrcode->code = $code;
            foreach ($data as $k=>$v) {
                $qrcode->$k = $v;
            }
            $qrcode->ctime = date('Y-m-d H:i:s');
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
