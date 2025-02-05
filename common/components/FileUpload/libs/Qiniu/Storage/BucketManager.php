<?php
namespace Qiniu\Storage;

use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Zone;
use Qiniu\Http\Client;
use Qiniu\Http\Error;

/**
 * 主要涉及了空间资源管理及批量操作接口的实现，具体的接口规格可以参考
 *
 * @link http://developer.qiniu.com/docs/v6/api/reference/rs/
 */
final class BucketManager
{
    private Auth $auth;
    private Zone $zone;

    public function __construct(Auth $auth, Zone $zone = null)
    {
        $this->auth = $auth;
        if ($zone === null) {
            $this->zone = new Zone();
        }
    }

    /**
     * 获取指定账号下所有的空间名。
     *
     * @return string[] 包含所有空间名
     */
    public function buckets(): array
    {
        return $this->rsGet('/buckets');
    }

    /**
     * 列取空间的文件列表
     *
     * @param $bucket   string  空间名
     * @param $prefix    string|null 列举前缀
     * @param $marker    string|null 列举标识符
     * @param $limit      int 单次列举个数限制
     * @param $delimiter  string|null 指定目录分隔符
     *
     * @return array    包含文件信息的数组，类似：[
     *                                              {
     *                                                 "hash" => "<Hash string>",
     *                                                  "key" => "<Key string>",
     *                                                  "fsize" => "<file size>",
     *                                                  "putTime" => "<file modify time>"
     *                                              },
     *                                              ...
     *                                            ]
     * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/list.html
     */
    public function listFiles(string $bucket, string $prefix = null, string $marker = null, int $limit = 1000, string $delimiter = null): array
    {
        $query = array('bucket' => $bucket);
        \Qiniu\setWithoutEmpty($query, 'prefix', $prefix);
        \Qiniu\setWithoutEmpty($query, 'marker', $marker);
        \Qiniu\setWithoutEmpty($query, 'limit', $limit);
        \Qiniu\setWithoutEmpty($query, 'delimiter', $delimiter);
        $url = Config::RSF_HOST . '/list?' . http_build_query($query);
        list($ret, $error) = $this->get($url);
        if ($ret === null) {
            return array(null, null, $error);
        }
        $marker = array_key_exists('marker', $ret) ? $ret['marker'] : null;
        return array($ret['items'], $marker, null);
    }

    /**
     * 获取资源的元信息，但不返回文件内容
     *
     * @param $bucket   string  待获取信息资源所在的空间
     * @param $key       string 待获取资源的文件名
     *
     * @return array    包含文件信息的数组，类似：
     *                                              [
     *                                                  "hash" => "<Hash string>",
     *                                                  "key" => "<Key string>",
     *                                                  "fsize" => "<file size>",
     *                                                  "putTime" => "<file modify time>"
     *                                              ]
     *
     * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/stat.html
     */
    public function stat(string $bucket, string $key): array
    {
        $path = '/stat/' . \Qiniu\entry($bucket, $key);
        return $this->rsGet($path);
    }

    /**
     * 删除指定资源
     *
     * @param $bucket   string  待删除资源所在的空间
     * @param $key       string 待删除资源的文件名
     *
     * @return mixed      成功返回NULL，失败返回对象Qiniu\Http\Error
     * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/delete.html
     */
    public function delete(string $bucket, $key): mixed
    {
        $path = '/delete/' . \Qiniu\entry($bucket, $key);
        list(, $error) = $this->rsPost($path);
        return $error;
    }


    /**
     * 给资源进行重命名，本质为move操作。
     *
     * @param $bucket   string  待操作资源所在空间
     * @param $oldname    string 待操作资源文件名
     * @param $newname    string 目标资源文件名
     *
     * @return mixed      成功返回NULL，失败返回对象Qiniu\Http\Error
     */
    public function rename(string $bucket, string $oldname, string $newname)
    {
        return $this->move($bucket, $oldname, $bucket, $newname);
    }

    /**
     * 给资源进行重命名，本质为move操作。
     *
     * @param $from_bucket  string   待操作资源所在空间
     * @param $from_key     string   待操作资源文件名
     * @param $to_bucket    string   目标资源空间名
     * @param $to_key       string   目标资源文件名
     *
     * @return mixed      成功返回NULL，失败返回对象Qiniu\Http\Error
     * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/copy.html
     */
    public function copy($from_bucket, $from_key, $to_bucket, $to_key)
    {
        $from = \Qiniu\entry($from_bucket, $from_key);
        $to = \Qiniu\entry($to_bucket, $to_key);
        $path = '/copy/' . $from . '/' . $to;
        list(, $error) = $this->rsPost($path);
        return $error;
    }

    /**
     * 将资源从一个空间到另一个空间
     *
     * @param $from_bucket  string   待操作资源所在空间
     * @param $from_key     string   待操作资源文件名
     * @param $to_bucket    string   目标资源空间名
     * @param $to_key       string   目标资源文件名
     *
     * @return mixed      成功返回NULL，失败返回对象Qiniu\Http\Error
     * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/move.html
     */
    public function move(string $from_bucket, string $from_key, string $to_bucket, string $to_key): mixed
    {
        $from = \Qiniu\entry($from_bucket, $from_key);
        $to = \Qiniu\entry($to_bucket, $to_key);
        $path = '/move/' . $from . '/' . $to;
        list(, $error) = $this->rsPost($path);
        return $error;
    }

    /**
     * 主动修改指定资源的文件类型
     *
     * @param $bucket string    待操作资源所在空间
     * @param $key    string    待操作资源文件名
     * @param $mime   string    待操作文件目标mimeType
     *
     * @return mixed      成功返回NULL，失败返回对象Qiniu\Http\Error
     * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/chgm.html
     */
    public function changeMime(string $bucket, string $key, string $mime): mixed
    {
        $resource = \Qiniu\entry($bucket, $key);
        $encode_mime = \Qiniu\base64_urlSafeEncode($mime);
        $path = '/chgm/' . $resource . '/mime/' .$encode_mime;
        list(, $error) = $this->rsPost($path);
        return $error;
    }

    /**
     * 从指定URL抓取资源，并将该资源存储到指定空间中
     *
     * @param $url     string   指定的URL
     * @param $bucket  string   目标资源空间
     * @param $key     string|null   目标资源文件名
     *
     * @return array    包含已拉取的文件信息。
     *                         成功时：  [
     *                                          [
     *                                              "hash" => "<Hash string>",
     *                                              "key" => "<Key string>"
     *                                          ],
     *                                          null
     *                                  ]
     *
     *                         失败时：  [
     *                                          null,
     *                                         Qiniu/Http/Error
     *                                  ]
     * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/fetch.html
     */
    public function fetch(string $url, string $bucket, string $key = null): array
    {

        $resource = \Qiniu\base64_urlSafeEncode($url);
        $to = \Qiniu\entry($bucket, $key);
        $path = '/fetch/' . $resource . '/to/' . $to;

        $ak = $this->auth->getAccessKey();
        $ioHost = $this->zone->getIoHost($ak, $bucket);

        $url = $ioHost . $path;
        return $this->post($url, null);
    }

    /**
     * 从镜像源站抓取资源到空间中，如果空间中已经存在，则覆盖该资源
     *
     * @param $bucket  string   待获取资源所在的空间
     * @param $key     string   代获取资源文件名
     *
     * @return mixed      成功返回NULL，失败返回对象Qiniu\Http\Error
     * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/prefetch.html
     */
    public function prefetch(string $bucket, string $key): mixed
    {
        $resource = \Qiniu\entry($bucket, $key);
        $path = '/prefetch/' . $resource;

        $ak = $this->auth->getAccessKey();
        $ioHost = $this->zone->getIoHost($ak, $bucket);

        $url = $ioHost . $path;
        list(, $error) = $this->post($url, null);
        return $error;
    }

    /**
     * 在单次请求中进行多个资源管理操作
     *
     * @param $operations   array  资源管理操作数组
     *
     * @return array 每个资源的处理情况，结果类似：
     *              [
     *                   { "code" => <HttpCode int>, "data" => <Data> },
     *                   { "code" => <HttpCode int> },
     *                   { "code" => <HttpCode int> },
     *                   { "code" => <HttpCode int> },
     *                   { "code" => <HttpCode int>, "data" => { "error": "<ErrorMessage string>" } },
     *                   ...
     *               ]
     * @link http://developer.qiniu.com/docs/v6/api/reference/rs/batch.html
     */
    public function batch(array $operations): array
    {
        $params = 'op=' . implode('&op=', $operations);
        return $this->rsPost('/batch', $params);
    }

    private function rsPost($path, $body = null): array
    {
        $url = Config::RS_HOST . $path;
        return $this->post($url, $body);
    }

    private function rsGet($path): array
    {
        $url = Config::RS_HOST . $path;
        return $this->get($url);
    }

    private function ioPost($path, $body = null): array
    {
        $url = Config::IO_HOST . $path;
        return $this->post($url, $body);
    }

    private function get($url): array
    {
        $headers = $this->auth->authorization($url);
        $ret = Client::get($url, $headers);
        if (!$ret->ok()) {
            return array(null, new Error($url, $ret));
        }
        return array($ret->json(), null);
    }

    private function post($url, $body): array
    {
        $headers = $this->auth->authorization($url, $body, 'application/x-www-form-urlencoded');
        $ret = Client::post($url, $body, $headers);
        if (!$ret->ok()) {
            return array(null, new Error($url, $ret));
        }
        $r = ($ret->body === null) ? array() : $ret->json();
        return array($r, null);
    }

    public static function buildBatchCopy($source_bucket, $key_pairs, $target_bucket): array
    {
        return self::twoKeyBatch('copy', $source_bucket, $key_pairs, $target_bucket);
    }


    public static function buildBatchRename($bucket, $key_pairs): array
    {
        return self::buildBatchMove($bucket, $key_pairs, $bucket);
    }


    public static function buildBatchMove($source_bucket, $key_pairs, $target_bucket): array
    {
        return self::twoKeyBatch('move', $source_bucket, $key_pairs, $target_bucket);
    }


    public static function buildBatchDelete($bucket, $keys): array
    {
        return self::oneKeyBatch('delete', $bucket, $keys);
    }


    public static function buildBatchStat($bucket, $keys): array
    {
        return self::oneKeyBatch('stat', $bucket, $keys);
    }

    private static function oneKeyBatch($operation, $bucket, $keys): array
    {
        $data = array();
        foreach ($keys as $key) {
            $data[] = $operation . '/' . \Qiniu\entry($bucket, $key);
        }
        return $data;
    }

    private static function twoKeyBatch($operation, $source_bucket, $key_pairs, $target_bucket): array
    {
        if ($target_bucket === null) {
            $target_bucket = $source_bucket;
        }
        $data = array();
        foreach ($key_pairs as $from_key => $to_key) {
            $from = \Qiniu\entry($source_bucket, $from_key);
            $to = \Qiniu\entry($target_bucket, $to_key);
            $data[] = $operation . '/' . $from . '/' . $to;
        }
        return $data;
    }
}
