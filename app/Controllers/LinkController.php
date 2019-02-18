<?php

//Thanks to http://blog.csdn.net/jollyjumper/article/details/9823047

namespace App\Controllers;

use App\Models\Link;
use App\Models\User;
use App\Models\Node;
use App\Models\Relay;
use App\Models\Smartline;
use App\Utils\ConfRender;
use App\Utils\Tools;
use App\Utils\URL;
use App\Services\Config;

/**
 *  HomeController
 */
class LinkController extends BaseController
{
    public function __construct()
    {
    }

    public static function GenerateRandomLink()
    {
        $i = 0;
        for ($i = 0; $i < 10; $i++) {
            $token = Tools::genRandomChar(16);
            $Elink = Link::where("token", "=", $token)->first();
            if ($Elink == null) {
                return $token;
            }
        }

        return "couldn't alloc token";
    }

    public static function GenerateSSRSubCode($userid, $without_mu)
    {
        $Elink = Link::where("type", "=", 11)->where("userid", "=", $userid)->where("geo", $without_mu)->first();
        if ($Elink != null) {
            return $Elink->token;
        }
        $NLink = new Link();
        $NLink->type = 11;
        $NLink->address = "";
        $NLink->port = 0;
        $NLink->ios = 0;
        $NLink->geo = $without_mu;
        $NLink->method = "";
        $NLink->userid = $userid;
        $NLink->token = LinkController::GenerateRandomLink();
        $NLink->save();

        return $NLink->token;
    }

    public static function GetContent($request, $response, $args)
    {
        $token = $args['token'];

        //$builder->getPhrase();
        $Elink = Link::where("token", "=", $token)->first();
        if ($Elink == null) {
            return null;
        }

        if ($Elink->type != 11) {
            return null;
        }

        $user = User::where("id", $Elink->userid)->first();
        if ($user == null) {
            return null;
        }

        $extend = 0;
        if (isset($request->getQueryParams()["extend"])) {
            $extend = (int)$request->getQueryParams()["extend"];
        }

        $sub = 0;
        if (isset($request->getQueryParams()["sub"])) {
            $sub = (int)$request->getQueryParams()["sub"];
        }

        // apps
        $opts = [];
        
        $ssd = 0;
        if (isset($request->getQueryParams()["ssd"])) {
            $ssd = (int)$request->getQueryParams()["ssd"];
        }

        $clash = 0;
        if (isset($request->getQueryParams()["clash"])) {
            $clash = (int)$request->getQueryParams()["clash"];
            $opts['dns'] = "0";
            if (isset($request->getQueryParams()["dns"])) {
                $opts['dns'] = (int)$request->getQueryParams()["dns"];
            }
            if (isset($request->getQueryParams()["secret"])) {
                $opts['secret'] = urldecode($request->getQueryParams()["secret"]);
            }
            if (isset($request->getQueryParams()["log-level"])) {
                $opts['log-level'] = urldecode($request->getQueryParams()["log-level"]);
            }
        }

        $surge = 0;
        if (isset($request->getQueryParams()["surge"])) {
            $surge = (int)$request->getQueryParams()["surge"];
        }

        $quantumult = 0;
        if (isset($request->getQueryParams()["quantumult"])) {
            $quantumult = (int)$request->getQueryParams()["quantumult"];
        }

        $surfboard = 0;
        if (isset($request->getQueryParams()["surfboard"])) {
            $surfboard = (int)$request->getQueryParams()["surfboard"];
        }

        if (in_array($quantumult, array(1, 2, 3))) {
            $newResponse = $response->withHeader('Content-type', ' application/octet-stream; charset=utf-8')->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')->withHeader('Content-Disposition', ' attachment; filename=Quantumult.conf');
            $newResponse->getBody()->write(LinkController::GetQuantumult($user, $quantumult));
            return $newResponse;
        }
        elseif (in_array($surge, array(1, 2, 3))) {
            $newResponse = $response->withHeader('Content-type', ' application/octet-stream; charset=utf-8')->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')->withHeader('Content-Disposition', ' attachment; filename=Surge.conf');
            $newResponse->getBody()->write(LinkController::GetSurge($user, $surge));
            return $newResponse;
        }
        elseif ($surfboard == 1) {
            $newResponse = $response->withHeader('Content-type', ' application/octet-stream; charset=utf-8')->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')->withHeader('Content-Disposition', ' attachment; filename=Surfboard.conf');
            $newResponse->getBody()->write(LinkController::GetSurfboard($user));
            return $newResponse;
        }
        elseif ($clash == 1) {
            $newResponse = $response->withHeader('Content-type', ' application/octet-stream; charset=utf-8')->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')->withHeader('Content-Disposition', ' attachment; filename=config.yml');
            $newResponse->getBody()->write(LinkController::GetClash($user, $opts));
            return $newResponse;
        }
        elseif ($ssd == 1) {
            $newResponse = $response->withHeader('Content-type', ' application/octet-stream; charset=utf-8')->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')->withHeader('Content-Disposition', ' attachment; filename=SSD.txt');
            $newResponse->getBody()->write(LinkController::GetSSD($user));
            return $newResponse;
        }
        else {
            $newResponse = $response->withHeader('Content-type', ' application/octet-stream; charset=utf-8')->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')->withHeader('Content-Disposition', ' attachment; filename=' . $token . '.txt');
            $newResponse->getBody()->write(LinkController::GetSub($user, $sub, $extend));
            return $newResponse;
        }
    }
    
    public static function GetSubinfo($user, $int = 0)
    {
        if ($int == 0) {
            $int = "";
        }
        $userapiUrl = Config::get('subUrl') . LinkController::GenerateSSRSubCode($user->id, 0);
        $return_info = [
            "link" => $userapiUrl,
            // sub
            "ss" => $userapiUrl . "?sub=2",
            "ssr" => $userapiUrl . "?sub=1",
            "v2ray" => $userapiUrl . "?sub=3",
            "v2ray_ss" => $userapiUrl . "?sub=4",
            "v2ray_ss_ssr" => $userapiUrl . "?sub=5",
            // apps
            "ssd" => $userapiUrl . "?ssd=1",
            "clash" => $userapiUrl . "?clash=1",
            "surge" => $userapiUrl . "?surge=" . $int,
            "surge_node" => $userapiUrl . "?surge=1",
            "surge2" => $userapiUrl . "?surge=2",
            "surge3" => $userapiUrl . "?surge=3",
            "surfboard" => $userapiUrl . "?surfboard=1",
            "quantumult" => $userapiUrl . "?quantumult=" . $int,
            "quantumult_v2" => $userapiUrl . "?quantumult=1",
            "quantumult_sub" => $userapiUrl . "?quantumult=2",
            "quantumult_conf" => $userapiUrl . "?quantumult=3",
        ];
        return $return_info;
    }

    public static function GetSurge($user, $surge = 0)
    {
        $subInfo = LinkController::GetSubinfo($user, $surge);
        $userapiUrl = $subInfo['surge'];
        $proxy_name = "";
        $proxy_group = "";
        $items = array_merge(URL::getAllItems($user, 0, 1), URL::getAllItems($user, 1, 1));
        foreach ($items as $item) {
            if (in_array($surge, array(1, 3))) {
                $proxy_group .= $item['remark'] . " = ss, " . $item['address'] . ", " . $item['port'] . ", encrypt-method=" . $item['method'] . ", password=" . $item['passwd'] . URL::getSurgeObfs($item) . ", tfo=true, udp-relay=true\n";
            }
            else {
                $proxy_group .= $item['remark'] . " = custom, " . $item['address'] . ", " . $item['port'] . ", " . $item['method'] . ", " . $item['passwd'] . ", https://raw.githubusercontent.com/lhie1/Rules/master/SSEncrypt.module" . URL::getSurgeObfs($item) . ", tfo=true\n";
            }
            $proxy_name .= ", ".$item['remark'];
        }
        if (in_array($surge, array(2, 3))) {
            $render = ConfRender::getTemplateRender();
            $render->assign('user', $user)
            ->assign('surge', $surge)
            ->assign('userapiUrl', $userapiUrl)
            ->assign('proxy_name', $proxy_name)
            ->assign('proxy_group', $proxy_group);
            return $render->fetch('surge.tpl');
        }
        else {
            return $proxy_group;
        }
    }
    
    public static function GetQuantumult($user, $quantumult = 0)
    {
        $subInfo = LinkController::GetSubinfo($user, 0);
        $proxys = [];
        $groups = [];
        $subUrl = "";
        if ($quantumult == 2) {
            $subUrl = $subInfo['link'];
        }
        else {
            $back_china_name = "";
            $v2ray_group = "";
            $v2ray_name = "";
            $v2rays = URL::getAllVMessUrl($user, 1);
            foreach($v2rays as $v2ray) {
                if (strpos($v2ray['ps'],"回国") or strpos($v2ray['ps'],"China")){
                   $back_china_name .="\n". $v2ray['ps'];
                }else{
                    $v2ray_name .= "\n" . $v2ray['ps'];
                }
                $v2ray_tls = ", over-tls=false, certificate=1";
                if ($v2ray['tls'] == "tls"){
                    $v2ray_tls = ", over-tls=true, tls-host=" . $v2ray['add'] . ", certificate=1";
                }
                $v2ray_obfs = "";
                if ($v2ray['net'] == "ws" || $v2ray['net'] == "http"){
                    $v2ray_obfs = ", obfs=" . $v2ray['net'] . ", obfs-path=\"" . $v2ray['path'] . "\", obfs-header=\"Host: " . $v2ray['add'] . "[Rr][Nn]User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_0 like Mac OS X) AppleWebKit/888.8.88 (KHTML, like Gecko) Mobile/6666666\"";
                }
                if ($v2ray['net'] == "kcp"){
                    $v2ray_group .= "";
                } else {
                    if ($quantumult == 1) {
                        $v2ray_group .= "vmess://" . base64_encode($v2ray['ps'] . " = vmess, " . $v2ray['add'] . ", " . $v2ray['port'] . ", chacha20-ietf-poly1305, \"" . $v2ray['id'] . "\", group=" . Config::get('appName') . "_v2" . $v2ray_tls . $v2ray_obfs) . "\n";
                    } else {
                        $v2ray_group .= $v2ray['ps'] . " = vmess, " . $v2ray['add'] . ", " . $v2ray['port'] . ", chacha20-ietf-poly1305, \"" . $v2ray['id'] . "\", group=" . Config::get('appName') . "_v2" . $v2ray_tls . $v2ray_obfs . "\n";
                    }
                }
            }
            if ($quantumult == 1) {
                return base64_encode($v2ray_group);
            }
            elseif ($quantumult == 3) {
                $ss_group = "";
                $ss_name = "";
                $items = array_merge(URL::getAllItems($user, 0, 1), URL::getAllItems($user, 1, 1));
                foreach($items as $item) {
                    $ss_group .= $item['remark'] . " = shadowsocks, " . $item['address'] . ", " . $item['port'] . ", " . $item['method'] . ", \"" . $item['passwd'] . "\", upstream-proxy=false, upstream-proxy-auth=false" . URL::getSurgeObfs($item) . ", group=" . Config::get('appName') . "\n";
                    if (strpos($item['remark'],"回国") or strpos($item['remark'],"China")){
                        $back_china_name .="\n". $item['remark'];
                    }else{
                        $ss_name .= "\n" . $item['remark'];
                    }
                }
                $ssr_group = "";
                $ssr_name = "";
                $ssrs = array_merge(URL::getAllItems($user, 0, 0), URL::getAllItems($user, 1, 0));
                foreach($ssrs as $item) {
                    $ssr_group .= $item['remark'] . " = shadowsocksr, " . $item['address'] . ", " . $item['port'] . ", " . $item['method'] . ", \"" . $item['passwd'] . "\", protocol=" . $item['protocol'] . ", protocol_param=" . $item['protocol_param'] . ", obfs=" . $item['obfs'] . ", obfs_param=\"" . $item['obfs_param'] . "\", group=" . Config::get('appName') . "\n";
                    if (strpos($item['remark'],"回国") or strpos($item['remark'],"China")){
                        $back_china_name .="\n". $item['remark'];
                    }else{
                        $ssr_name .= "\n" . $item['remark'];
                    }
                }
                $quan_proxy_group = base64_encode("🍃 Proxy  :  static, 🏃 Auto\n🏃 Auto\n🚀 Direct\n" . $ss_name . $ssr_name . $v2ray_name);
                $quan_auto_group = base64_encode("🏃 Auto  :  auto\n" . $ss_name . $ssr_name . $v2ray_name);
                $quan_domestic_group = base64_encode("🍂 Domestic  :  static, 🚀 Direct\n🚀 Direct\n🍃 Proxy\n".$back_china_name);
                $quan_others_group = base64_encode("☁️ Others  :   static, 🍃 Proxy\n🚀 Direct\n🍃 Proxy");
                $quan_apple_group = base64_encode("🍎 Only  :  static, 🚀 Direct\n🚀 Direct\n🍃 Proxy");
                $quan_direct_group = base64_encode("🚀 Direct : static, DIRECT\nDIRECT");
                $proxys = [
                    "ss" => $ss_group,
                    "ssr" => $ssr_group,
                    "v2ray" => $v2ray_group,
                ];
                $groups = [
                    "proxy_group" => $quan_proxy_group,
                    "auto_group" => $quan_auto_group,
                    "domestic_group" => $quan_domestic_group,
                    "others_group" => $quan_others_group,
                    "direct_group" => $quan_direct_group,
                    "apple_group" => $quan_apple_group,
                ];
            }
            else {
                return "悟空别闹...";
            }
        }
        $render = ConfRender::getTemplateRender();
        $render->assign('user', $user)
        ->assign('subUrl', $subUrl)
        ->assign('proxys', $proxys)
        ->assign('groups', $groups)
        ->assign('quantumult', $quantumult)
        ->assign('appName', Config::get('appName'));
        return $render->fetch('quantumult.tpl');
    }

    public static function GetSurfboard($user)
    {
        $subInfo = LinkController::GetSubinfo($user, 0);
        $userapiUrl = $subInfo['surfboard'];
        $ss_name = "";
        $ss_group = "";
        $items = array_merge(URL::getAllItems($user, 0, 1), URL::getAllItems($user, 1, 1));
        foreach($items as $item) {
            $ss_group .= $item['remark'] . " = ss, " . $item['address'] . ", " . $item['port'] . ", " . $item['method'] . ", " . $item['passwd'] . URL::getSurgeObfs($item) . "\n";
            $ss_name .= ", ".$item['remark'];
        }
        $render = ConfRender::getTemplateRender();
        $render->assign('user', $user)
        ->assign('userapiUrl', $userapiUrl)
        ->assign('ss_name', $ss_name)
        ->assign('ss_group', $ss_group);
        return $render->fetch('surfboard.tpl');
    }

    public static function GetClash($user, $opts)
    {
        $subInfo = LinkController::GetSubinfo($user, 0);
        $userapiUrl = $subInfo['clash'];
        $confs = [];
        $proxy_confs = [];
        $back_china_confs=[];
        // ss
        $items = array_merge(URL::getAllItems($user, 0, 1), URL::getAllItems($user, 1, 1));
        foreach ($items as $item) {
            $sss = [
                "name" => $item['remark'],
                "type" => "ss",
                "server" => $item['address'],
                "port" => $item['port'],
                "cipher" => $item['method'],
                "password" => $item['passwd'],
            ];
            if (in_array($item['obfs'], array("simple_obfs_tls", "simple_obfs_http"))) {
                if (strpos($item['obfs'], 'http')) {
                    $sss['obfs'] = "http";
                } else {
                    $sss['obfs'] = "tls";
                }
                if ($item['obfs_param'] != '') {
                    $sss['obfs-host'] = $item['obfs_param'];
                } else {
                    $sss['obfs-host'] = "wns.windows.com";
                }
            }
            if (strpos($sss['name'],"回国") or strpos($sss['name'],"China")){
                $back_china_confs[] = $sss;
            }else{
                $proxy_confs[] = $sss;
            }
            $confs[] = $sss;
        }
        // v2
        $items = URL::getAllVMessUrl($user, 1);
        foreach ($items as $item) {
            $v2rays = [
                "name" => $item['ps'],
                "type" => "vmess",
                "server" => $item['add'],
                "port" => $item['port'],
                "uuid" => $item['id'],
                "alterId" => $item['aid'],
                "cipher" => "auto",
            ];
            if ($item['net'] == "ws") {
                $v2rays['network'] = 'ws';
                $v2rays['ws-path'] = $item['path'];
                if ($item['tls'] == 'tls') {
                    $v2rays['tls'] = true;
                }
            } elseif ($item['net'] == "tls") {
                $v2rays['tls'] = true;
            }
            if (strpos($v2rays['name'],"回国") or strpos($v2rays['name'],"China")){
                $back_china_confs[] = $v2rays;
            }else{
                $proxy_confs[] = $v2rays;
            }
            $confs[] = $v2rays;
        }

        $render = ConfRender::getTemplateRender();
        $render->assign('user', $user)
        ->assign('userapiUrl', $userapiUrl)
        ->assign('opts', $opts)
        ->assign('confs', $confs)
        ->assign('proxies', array_map(function ($conf) {
                return $conf['name'];
            }, $proxy_confs))
        ->assign('back_china_proxies',array_map(function ($conf) {
            return $conf['name'];
        }, $back_china_confs));
        return $render->fetch('clash.tpl');
    }

    public static function GetSSD($user)
    {
        return URL::getAllSSDUrl($user);
    }

    public static function GetSub($user, $sub, $extend)
    {
        $return_url = '';
        // SSR
        if ($sub == 1) {
            $return_url .= URL::getAllUrl($user, 0, 0, $extend).PHP_EOL;
            return Tools::base64_url_encode($return_url);
        }
        // SS
        elseif ($sub == 2) {
            $return_url .= URL::getAllUrl($user, 0, 1, $extend).PHP_EOL;
            return Tools::base64_url_encode($return_url);
        }
        // V2
        elseif ($sub == 3) {
            return Tools::base64_url_encode(URL::getAllVMessUrl($user));
        }
        // V2 + SS
        elseif ($sub == 4) {
            $return_url .= URL::getAllVMessUrl($user).PHP_EOL;
            $return_url .= URL::getAllUrl($user, 0, 1, $extend).PHP_EOL;
            return Tools::base64_url_encode($return_url);
        }
        // V2 + SS + SSR
        elseif ($sub == 5) {
            $return_url .= URL::getAllVMessUrl($user).PHP_EOL;
            $return_url .= URL::getAllUrl($user, 0, 0, $extend).PHP_EOL;
            $return_url .= URL::getAllUrl($user, 0, 1, $extend).PHP_EOL;
            return Tools::base64_url_encode($return_url);
        }
    }
}
