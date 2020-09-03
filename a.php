<?php
/**
 * 微早教SCRM JwClassController.class.module文件
 * ----------------------------------------------
 * 班课教务-班级控制器
 * ==============================================
 * 版权所有 2020 http://www.weizaojiao.cn
 * ==============================================
 * @date 2020/7/23 10:53
 * @author: 周章亮（zhouzhangliang@weizaojiao.cn）
 * @version: 1.0
 */

namespace Business\Controller;

use Org\Util\Response;
use Business\enum\JwClassEnum;
class JwClassController extends IndexController
{
    protected $size = 20;
    protected $response, $jwLog, $member,
        $jwClass, $jwCourse, $jwClassRoom,
        $jwClassTerm, $jwClassRule, $jwStudentReg,
        $jwCourseStage, $jwClassTermGapperiod;

    public function __construct()
    {
        parent::__construct();
        $this->response = Response::start();
        $this->jwLog = D('JwLog');
        $this->member = D('member');
        $this->jwClass = D('JwClass');
        $this->jwCourse = D('JwCourse');
        $this->jwClassRule = D('JwClassRule');
        $this->jwClassRoom = D('JwClassRoom');
        $this->jwClassTerm = D('JwClassTerm');
        $this->jwStudentReg = D('jwStudentReg');
        $this->jwCourseStage = D('JwCourseStage');
        $this->jwClassTermGapperiod = D('JwClassTermGapperiod');
    }
    public  function aa(){

    }

    /**
     * 通用选项下拉列表数据
     *
     * 搜索选项预选数据
     * 1.课程名称 课程分类->课程名称
     * 2. 所有教室名
     *
     * 创建班级预选数据
     * 1.授课老师
     * 2.辅班老师
     * 3.班级分类
     */
    public function common_option()
    {
        $course_stage = $this->jwCourse
            ->selectCourseStage($this->org_id);
        $member = $this->member
            ->getMember($this->org_id, 'Tc');
        $course = array_column(
            $this->jwCourse->selectCourseList($this->org_id, 'id, title'),
            'title',
            'id'
        );
        $data = [
            // 课阶分类和名称
            'course_stage' => $course_stage,
            // 上课教室
            'room'            => $this->jwClassRoom->getNameByOrgid($this->org_id),
            // 授课老师
            'teaching'        => array_column($member, 'nickname', 'uid'),
            // 班级新增中的信息
            // 班级分类 course_stage方法获取课阶名称
            'course'          => $course + ['-1' => '特色课程']
        ];
        $this->response->show(200, 'success', $data);
    }

    /**
     * 班级管理列表
     *  type值解析
     *  1 课程名称
     *  2 周几上课
     *  3 上课教室
     *  4 授课老师
     *  5 助教老师
     *  6 是否允许家长端报班
     *  $condition 排课表查询条件
     *  $where 班级表查询条件
     */
    public function aa(){
        
    }

    public function index()
    {
        // 选择框查询
        $type = I('request.type');
        $type_val = I('request.type_val', '', 'trim');
        if ($type > 6) {
            $this->response->show(400, '请选择准确的查询条件');
        }
        switch ($type) {
            case 1:
                $where['jcs.id'] = $type_val;
                break;
            case 2:
                $condition['week'] = $type_val;
                break;
            case 3:
                $where['jc.ro_id'] = $type_val;
                break;
            case 4:
                $where['tc.nickname'] = ['like', '%' . $type_val . '%'];
                break;
            case 5:
                $where['zj.nickname'] = ['like', '%' . $type_val . '%'];
                break;
            case 6:
                $where['jc.parentsign'] = $type_val;
                break;
        }
        // 日期查询
        $sdate = I('request.sdate', '', 'trim');
        $edate = I('request.edate', '', 'trim');
        $where['jc.org_id'] = $this->org_id;
        if (!empty($sdate) && !empty($edate)) {
            $condition['ptdate'] = ['between', [$sdate, $edate]];
            $condition['org_id'] = $this->org_id;
            // 先查出排课的数据
            $class_rule = $this->jwClassRule
                ->where($condition)
                ->group('classid')
                ->select();
            $classids = array_column($class_rule, 'classid');
            if(empty($classids)){
                $this->response->show(200, '没有数据', []);
            }
            $where['jc.id'] = ['in', $classids];
        }
        // 组装查询条件查询班级
        $total = $this->jwClass->getListCount($where);
        list($limit, $size, $total_page) = paGing($this->size, $total);
        // 查出机构员工信息
        $member = $this->member->getNicknameByOrgid($this->org_id);
        $class_list = $this->jwClass->getList($where, $limit, $size);
        foreach ($class_list as $key => $val) {
            // 查出周几
            $weeks = [];
            $exp = array_unique(
                explode(',', $val['week'])
            );
            foreach ($exp as $v) {
                $weeks[] = JwClassEnum::WEEK[$v];
            }
            // 课阶月龄
            $class_list[$key]['startend_month'] = monthToAge($val['start_mouth']).'-'.monthToAge($val['end_mouth']);
            $class_list[$key]['week'] = $weeks;
            // 查出学期
            $class_rule_v = $this->jwClassRule
                ->where(['classid' => $val['id']])
                ->field('min(ptdate) as min_ptdate, max(ptdate) as max_ptdate')
                ->find();
            $term = $this->jwClassTerm->where([
                'org_id'     => $this->org_id,
                'start_date' => ['ELT', $class_rule_v['min_ptdate']],
                'end_date'   => ['EGT', $class_rule_v['max_ptdate']]
            ])->getField('term', true);
            $terms = [];
            foreach ($term as $v) {
                $terms[] = JwClassEnum::TERM[$v - 1];
            }
            // 剩余课程节数 当前日期大于等于排课日期的数据
            $class_list[$key]['remaining_num'] = $this->jwClassRule->where([
                'classid' => $val['id'],
                'ptdate'  => ['EGT', date('Y-m-d')]
            ])->count();
            $class_list[$key]['term'] = $terms;
            // 查出机构员工信息 创建人及教师等
            $class_list[$key]['cj_name'] = $member[$val['uid']];
            $class_list[$key]['startend_date'] = $class_rule_v['min_ptdate'] . ' 至 ' . $class_rule_v['max_ptdate'];
        }
        $data = [
            'total'      => (int)$total,
            'total_page' => $total_page,
            'data'       => $class_list
        ];
        $this->response->show(200, 'success', $data);
    }

    /**
     * 根据课阶id获取课阶名称
     * @param int $stage_id 班级ID
     */
    public function get_stage(){
        $stage_id = I('request.stage_id', 0, 'intval');
        $title = $this->jwCourseStage->where([
            'id' => $stage_id,
            'org_id' => $this->org_id
        ])->getField('title');
        $this->response->show(200, 'success', ['title' => $title]);
    }

    /**
     * 班级管理列表
     */
    public function class_index(){
        $this->display();
    }

    /**
     * 班级详情
     * @param int $classid 班级ID
     */
    public function class_detail()
    {
        $classid = I('request.classid', 0, 'intval');
        $class = $this->jwClass->getRuleinfoByClassid($classid);
        if (empty($classid) || empty($class)) {
            $this->response->show(400, '参数错误或没有此数据');
        }
        // 获取班级下的排课ID
        $classrule = $this->jwClassRule->where([
            'classid' => $classid
        ])->field('id, start_time, end_time, ptdate, week, ro_id, tc_id, zj_id, max_stu, status, ctime')
            ->order('ptdate desc, start_time desc')
            ->select();
        $member = $this->member->getNicknameByOrgid($this->org_id);
        $room = $this->jwClassRoom->getNameByOrgid($this->org_id);
        foreach ($classrule as $key => $val) {
            // 获取正式位
            $classrule[$key]['formal'] = $this->jwStudentReg->where([
                'ru_id' => $val['id'],
                'type'  => ['in', ['YY','QJ','QD','QQD']]
            ])->count();
            // 获取试听位
            $classrule[$key]['audition'] = $this->jwStudentReg->where([
                'ru_id' => $val['id'],
                'type'  => 'ST'
            ])->count();
            // 上课教室
            $classrule[$key]['room_name'] = $room[$val['ro_id']];
            $classrule[$key]['week'] = JwClassEnum::WEEK[$val['week']];
            // 状态
            $classrule[$key]['status'] = JwClassEnum::STATUS[$val['status']];
            $classrule[$key]['room_name'] = $room[$val['ro_id']];
            // 授课老师，辅班老师
            $classrule[$key]['zj_name'] = $member[$val['zj_id']];
            $classrule[$key]['tc_name'] = $member[$val['tc_id']];
        }
        $class['classrule'] = $classrule;
        $this->response->show(200, 'success', $class);
    }

    /**
     * 班级创建
     * 1.检测辅班老师和授课老师是否同一个
     * 2.家长端报班默认关闭
     *
     * 排课预览
     * 1.验证排课总数等于班级设置的课程总节数
     * 2.验证上课教室、上课老师、辅班老师是否被占用
     */
    public function class_add()
    {
        // 模拟数据
        /*$cs_id = 1;
        $ptdate = "2020-08-03";
        $start_time = '10:30';
        $end_time = '11:00';
        $ro_id = 1;
        $tc_id = 1;
        $zj_id = 5;
        $hour = 1.5;
        $max_stu = 12;
        $course_num = 4;
        $parentsign = 0;
        $class_rule = [
            '2020-08-03',
            '2020-08-06',
            '2020-08-07',
            '2020-08-09',
        ];
        foreach ($class_rule as $v) {
            $week_exp[] = date("w", strtotime($v));
        }
        $week = implode(',', $week_exp);*/
        $cs_id = I('post.cs_id');
        $start_time = I('post.start_time');
        $end_time = I('post.end_time');
        $ro_id = I('post.ro_id', 0, 'intval');
        $tc_id = I('post.tc_id', 0, 'intval');
        $zj_id = I('post.zj_id', 0, 'intval');
        // 单节消耗课时必须是0.5的倍数
        $hour = I('post.hour');
        $max_stu = I('post.max_stu', 0, 'intval');
        $course_num = I('post.course_num');
        $parentsign = I('post.parentsign', 0, 'intval');
        // 排课数据 接收过来是数组 包裹着班级选择节数对应数量的日期
        $class_rule = I('post.class_rule');
        if(empty($class_rule) || empty($cs_id) || empty($start_time) || empty($end_time) || empty($ro_id) || empty($tc_id) || empty($zj_id) || empty($hour) || empty($max_stu) || empty($course_num)){
            $this->response->show(400, '信息不能为空');
        }
        foreach ($class_rule as $v) {
            $week_exp[] = date("w", strtotime($v));
        }
        // 为防止排课信息更改日期,ptdate开课日期为排课信息中的第一个日期
        sort($class_rule);
        $ptdate = $class_rule[0];
        $week = implode(',', $week_exp);
        $uid = $this->uid;
        $org_id = $this->org_id;
        // 检测日期格式是否正确
        if (strlen($ptdate) != 10) {
            $this->response->show(400, '开班日期格式错误');
        }
        // 验证排课总数是否等于班级设置的总节数
        if (empty($class_rule) || $course_num != count($class_rule)) {
            $this->response->show(400, '抱歉，排课节数小于或已超出班级设置，请再次点击添加');
        }
        // 检测课时倍数
        if (($hour * 10) % 5 != 0) {
            $this->response->show(400, '单节消耗课时请输入0.5的倍数');
        }
        // 检测授课老师和辅班老师是否一致
        if ($tc_id == $zj_id) {
            $this->response->show(400, '辅班老师和授课老师不能选择同一个教室');
        }
        // 检测排课日期是否小于开班日期
        foreach ($class_rule as $v) {
            if (strtotime($v) < strtotime($ptdate)) {
                $this->response->show(400, '排课日期不能小于开班日期');
                break;
            }
            // 验证上课教室 上课老师 辅班老师是否被占用
            $occupy_room = $this->jwClassRule
                ->checkOccupy($this->org_id, $ro_id, $v, $start_time, $end_time, JwClassEnum::OCCUPY_ROOM);
            if (!empty($occupy_room)) {
                $message = "抱歉，{$v}的{$start_time}的课程教室和其他课程冲突";
                $this->response->show(400, $message);
            }
            $occupy_tech = $this->jwClassRule
                ->checkOccupy($this->org_id, $tc_id, $v, $start_time, $end_time, JwClassEnum::OCCUPY_TECH);
            if (!empty($occupy_tech)) {
                $message = "抱歉，{$v}的{$start_time}的授课老师和其他课程冲突";
                $this->response->show(400, $message);
            }
            $occupy_zjch = $this->jwClassRule
                ->checkOccupy($this->org_id, $zj_id, $v, $start_time, $end_time, JwClassEnum::OCCUPY_ZJCH);
            if (!empty($occupy_zjch)) {
                $message = "抱歉，{$v}的{$start_time}的辅班老师和其他课程冲突";
                $this->response->show(400, $message);
            }
        }
        // 组装数据
        M()->startTrans();
        $jw_class_data['org_id'] = $org_id;
        $jw_class_data['cs_id'] = $cs_id;
        $jw_class_data['ptdate'] = $ptdate;
        $jw_class_data['start_time'] = $start_time;
        $jw_class_data['end_time'] = $end_time;
        $jw_class_data['ro_id'] = $ro_id;
        $jw_class_data['tc_id'] = $tc_id;
        $jw_class_data['zj_id'] = $zj_id;
        $jw_class_data['max_stu'] = $max_stu;
        $jw_class_data['hour'] = $hour;
        $jw_class_data['course_num'] = $course_num;
        $jw_class_data['week'] = $week;
        $jw_class_data['parentsign'] = $parentsign;
        $jw_class_data['uid'] = $uid;
        $jwclass_res = $this->jwClass->add($jw_class_data);
        $classid = $this->jwClass->getLastInsID();
        // 排课数据
        $class_rule_data = [];
        foreach ($class_rule as $k => $v) {
            $class_rule_data[$k]['org_id'] = $org_id;
            $class_rule_data[$k]['start_time'] = $start_time;
            $class_rule_data[$k]['end_time'] = $end_time;
            $class_rule_data[$k]['strtime'] = $start_time . '-' . $end_time;
            $class_rule_data[$k]['ptdate'] = $v;
            $class_rule_data[$k]['week'] = date("w", strtotime($v));
            $class_rule_data[$k]['cs_id'] = $cs_id;
            $class_rule_data[$k]['ro_id'] = $ro_id;
            $class_rule_data[$k]['tc_id'] = $tc_id;
            $class_rule_data[$k]['zj_id'] = $zj_id;
            $class_rule_data[$k]['max_stu'] = $max_stu;
            $class_rule_data[$k]['classid'] = $classid;
            // 当前排课处于班级的第几周
            $class_rule_data[$k]['week_num'] = $this->jwClassRule->getNowWeeksByTime($v);
        }
        $jwclassrule_res = $this->jwClassRule->addAll($class_rule_data);
        if ($jwclass_res && $jwclassrule_res) {
            $code = 200;
            $msg = 'success';
            M()->commit();
        } else {
            $code = 400;
            $msg = 'error';
            M()->rollback();
        }
        $this->response->show($code, $msg);
    }

    /**
     * 班级创建
     * 选择日期回显星期几
     */
    public function dayweek()
    {
        $date = I('post.date', '', 'trim');
        if (empty($date)) {
            $this->response->show(400, '请选择日期');
        }
        $this->response->show(200, 'success', JwClassEnum::WEEK[date("w", strtotime($date))]);
    }

    /**
     * 根据课阶分类查询课阶
     * 包含特色课程 标识为-1或者改为其他
     * @param string $course_id 课阶分类ID
     */
    public function course_stage()
    {
        $course_id = I('post.course_id', 0, 'intval');
        if (empty($course_id)) {
            $this->response->show(400, '课阶类型必传');
        }
        // 模拟特色课程分类ID为-1
        if ($course_id == -1) {
            $stage = M('course_stage')
                ->where([
                    'type'   => 1,
                    'status' => 1
                ])->select();
        } else {
            $stage = $this->jwCourseStage->getCourseStage($course_id);
        }
        $this->response->show(200, 'success', $stage);
    }

    /**
     * 是否允许家长端报班
     * @param int $classid 班级id
     * @param int $parentsign 报班状态
     */
    public function report_class()
    {
        $classid = I('post.classid', 0, 'intval');
        $parentsign = I('post.parentsign', 0, 'intval');
        $class = $this->jwClass
            ->field('id, parentsign')
            ->find($classid);
        if (empty($class) || !in_array($parentsign, [0, 1])) {
            $this->response->show(400, '数据错误');
        }
        $this->jwClass
            ->where(['id' => $classid])
            ->setField(['parentsign' => $parentsign]);
        // 记录报班日志
        $parentsign_option = ['禁止', '允许'];
        $class_log = [
            'befor' => ['parentsign' => $parentsign_option[$class['parentsign']]],
            'after' => ['parentsign' => $parentsign_option[$parentsign]]
        ];
        $log_data = [
            'type'     => '修改',
            'title'    => '是否允许家长端报班',
            'area'     => 'JWBJJZBBKG',
            'username' => session('user_auth')['nickname'],
            'json'     => json_encode($class_log),
            'classid'  => $classid,
            'org_id'   => $this->org_id,
        ];
        $this->jwLog->add($log_data);
        $this->response->show(200, 'success', $class);
    }

    /**
     * 根据开课日期和课程节数计算出约课日期
     * 以周为单位（7天）
     * @param string $ptdate
     * @param int $course_num
     */
    public function rule_date(){
        $ptdate = I('request.ptdate', '', 'trim');
        $course_num = I('request.course_num', 0, 'intval');
        if(empty($ptdate) || empty($course_num)){
            $this->response->show(200, 'success', []);
        }
        // TODO 隔过去空档期
        $ptdate = strtotime($ptdate);
        $ptdate_arr = [];
        for($i = 1; $i <= $course_num; $i++){
            $day = ($i * 7) - 7;
            array_push($ptdate_arr, date('Y-m-d', strtotime("+$day day", $ptdate)));
        }
        $this->response->show(200, 'success', $ptdate_arr);
    }

    /**
     * 点击排课预览返回空档期
     * 根据选择或切换的日期起始返回空档日期
     * @param string $date 日期
     */
    public function empty_term()
    {
        $date = I('request.ptdate', '', 'trim');
        $gapperiod_date = [];
        if (!empty($date)) {
            // 当月第一天
            $first_day = date('Y-m-01', strtotime("$date -1 month"));
            // 当月最后一天
            $last_day = date('Y-m-d', strtotime("$first_day +3 month -1 day"));
            // 获取区间内最小和最大日期
            $gapperiod = $this->jwClassTermGapperiod->where([
                'start_date' => ['EGT', $first_day],
                'end_date'   => ['ELT', $last_day]
            ])->field('start_date, end_date')->select();
            foreach($gapperiod as $k => $v){
                $gapperiod_date[] = $this->getDateRange($v['start_date'], $v['end_date']);
            }
            $result = array_reduce($gapperiod_date, 'array_merge', []);
        }
        $this->response->show(200, 'success', $result);
    }

    /**
     * 获取两个日期的中间时间段
     * @param $start
     * @param $end
     * @return array
     */
    function getDateRange($start, $end) {
        $range = [];
        for ($i = 0; strtotime($start . '+' . $i . ' days') <= strtotime($end); $i++) {
            $time = strtotime($start . '+' . $i . ' days');
            $range[] = date('Y-m-d', $time);
        }
        return $range;
    }

    /**
     * 课程变动
     * 数据返回
     * @param int $classid 班级ID
     */
    public function course_change()
    {
        $classid = I('request.classid', 0, 'intval');
        $class = $this->jwClass->getRuleinfoByClassid($classid);
        if (empty($classid) || empty($class)) {
            $this->response->show(400, '参数错误或没有此数据');
        }
        // 获取班级下的排课ID
        $classrule = $this->jwClassRule->where([
            'classid' => $class['id']
        ])->field('id, start_time, end_time, ptdate, week, ro_id, tc_id, zj_id, max_stu, status, ctime')
            ->order('ptdate desc, start_time desc')
            ->select();
        $member = $this->member->getNicknameByOrgid($this->org_id);
        $room = $this->jwClassRoom->getNameByOrgid($this->org_id);
        foreach ($classrule as $key => $val) {
            // 获取正式位
            $classrule[$key]['formal'] = $this->jwStudentReg->where([
                'ru_id' => $val['id'],
                'type'  => ['in', ['YY', 'QD', 'QQD', 'QJ']]
            ])->count();
            // 获取试听位
            $classrule[$key]['audition'] = $this->jwStudentReg->where([
                'ru_id' => $val['id'],
                'type'  => 'ST'
            ])->count();
            // 上课教室
            $classrule[$key]['room_name'] = $room[$val['ro_id']];
            // 状态
            $classrule[$key]['status'] = JwClassEnum::STATUS[$val['status']];
            $classrule[$key]['room_name'] = $room[$val['ro_id']];
            // 授课老师，辅班老师
            $classrule[$key]['zj_name'] = $member[$val['zj_id']];
            $classrule[$key]['tc_name'] = $member[$val['tc_id']];
            // 是否是已过期的给前端标识不能选中
            $is_overdue = 1;
            if (strtotime($val['ptdate']) < strtotime(date('Y-m-d'))) {
                $is_overdue = 0;
            }
            $classrule[$key]['is_expre'] = $is_overdue;
        }
        $class['class_rule'] = $classrule;
        $this->response->show(200, 'success', $class);
    }

    /**
     * 班级变动
     * 停启课
     * @param string $classrule_ids 排课ID 多个逗号分隔
     * @param int $status 状态
     */
    public function change_state()
    {
        $classrule_ids = I('post.classrule_ids', '', 'trim');
        $status = I('post.status', 1, 'intval');
        $class_rule = $this->jwClassRule->where([
            'id' => ['in', $classrule_ids]
        ])->select();
        if (empty($classrule_ids) || !in_array($status, [1, 2]) || empty($class_rule)) {
            $this->response->show(400, '抱歉，请先勾选课程，才能进行操作');
        }
        if ($status == 1) {
            // 检测启用课程的时候教室和老师是否会冲突
            foreach ($class_rule as $key => $val) {
                // 验证上课教室 上课老师 辅班老师是否被占用
                $occupy_room = $this->jwClassRule
                    ->checkOccupy($this->org_id,$val['ro_id'], $val['ptdate'], $val['start_time'], $val['end_time'], JwClassEnum::OCCUPY_ROOM, $val['id']);
                if (!empty($occupy_room)) {
                    $message = "抱歉，{$val['ptdate']}的{$val['start_time']}的课程教室和其他课程冲突";
                    $this->response->show(400, $message);
                }
                $occupy_tech = $this->jwClassRule
                    ->checkOccupy($this->org_id,$val['tc_id'], $val['ptdate'], $val['start_time'], $val['end_time'], JwClassEnum::OCCUPY_TECH, $val['id']);
                if (!empty($occupy_tech)) {
                    $message = "抱歉，{$val['ptdate']}的{$val['start_time']}的授课老师和其他课程冲突";
                    $this->response->show(400, $message);
                }
                $occupy_zjch = $this->jwClassRule
                    ->checkOccupy($this->org_id,$val['zj_id'], $val['ptdate'], $val['start_time'], $val['end_time'], JwClassEnum::OCCUPY_ZJCH, $val['id']);
                if (!empty($occupy_zjch)) {
                    $message = "抱歉，{$val['ptdate']}的{$val['start_time']}的辅班老师和其他课程冲突";
                    $this->response->show(400, $message);
                }
            }
        }
        if ($status == 2) {
            // 检测是否是历史课程 历史课程不支持停课 状态为2的时候
            foreach ($class_rule as $key => $val) {
                if ($val['ptdate'] <= strtotime(date('Y-m-d'))) {
                    $this->response->show(400, '所选排课中包含历史课程，不支持停课');
                }
            }
            // 获取班级ID
            $today_date = date('Y-m-d');
            $classids = $this->jwClassRule->where([
                'ptdate' => $today_date,
                'id'     => ['in', $classrule_ids]
            ])->getField('classid', true);
            if (!empty($classids)) {
                // 检测是否有学员已经考勤
                $student_reg = $this->jwStudentReg->where([
                    'type'    => 'QD',
                    'classid' => ['in', $classids]
                ])->count();
                if ($student_reg > 0) {
                    $this->response->show(400, '今日课程已有学员已考勤，不支持停课操作');
                }
            }
            $data['credit'] = 0;
            $data['uid'] = session('user_auth')['uid'];
            $data['org_id'] = $this->org_id;
            $data['remark'] = '取消';
            $data['type_status'] = 'QYY';
            $data['message'] = '';
            // 找出已约课学员进行退课
            // 已预约未考勤或补课
            $students = $this->jwStudentReg->where([
                'org_id' => $this->org_id,
                'ru_id' => ['in', $classrule_ids],
                'type' => 'YY'
            ])->select();
            foreach($students as $key => $val){
                $rs = $this->jwStudentReg->putRegStatusNew($val['id'], $data, $val);
                if($rs['status'] != 1){
                    $this->response->show(400, $rs['msg']);
                }
            }
        }
        $this->jwClassRule
            ->where(['id' => ['in', $classrule_ids]])
            ->setField(['status' => $status]);
        // 返还修改后的状态
        $class_rule = $this->jwClassRule
            ->where(['id' => ['in', $classrule_ids]])
            ->field('id, classid, status')
            ->select();
        $this->response->show(200, 'success', $class_rule);
    }

    /**
     * 班级变动
     * 弹框数据
     * @param $classid 班级ID
     * @param string $classrule_ids 班级排课ID 多个逗号分隔
     */
    public function change_popup()
    {
        $classid = I('post.classid', 0, 'intval');
        $classrule_ids = I('post.classrule_ids', '', 'trim');
        if (empty($classid) || empty($classrule_ids)) {
            $this->response->show(400, '参数错误');
        }
        $class_rule_status = $this->jwClassRule->where([
            'id' => ['in', $classrule_ids]
        ])->getField('status', true);
        // 检测所选行内课程必须是启用状态的课程 2是停课状态 0是下架状态
        if (in_array(2, $class_rule_status) || in_array(0, $class_rule_status)) {
            $this->response->show(400, '所选课程必须是全部启用状态的课程');
        }
        // 查出课程名称及分类合并到class信息中
        $class = $this->jwClass
            ->where(['id' => $classid])
            ->field('id, org_id, ctime, cs_id')
            ->find();
        $stage = $this->jwCourseStage
            ->stageByCourseRow($class['cs_id']);
        $class['title'] = $stage['title'];
        $class['sub_title'] = $stage['sub_title'];
        $class['category_name'] = $stage['category_name'];
        // 根据班级排课ID查出排课
        $class_rule = $this->jwClassRule
            ->getClassRuleByids($classrule_ids);
        $class['class_rule'] = $class_rule;
        // 如果是只展示一条的话就回显数据，多条的话不回显
        $class_rule_data = [];
        if (false === strpos($classrule_ids, ',')) {
            $class_rule_data = $this->jwClassRule
                ->where(['id' => $classrule_ids])
                ->field('start_time, end_time, ptdate, ro_id, tc_id, zj_id, max_stu')
                ->find();
        }
        $class['class_rule_data'] = $class_rule_data;
        $this->response->show(200, 'success', $class);
    }

    /**
     * 班级变动
     * 完成并同步课表
     */
    public function sync_curriculum()
    {
        $classid = I('post.classid', 0, 'intval');
        // 查出班级信息
        $class = $this->jwClass->find($classid);

        if (!$classid && empty($class)) {
            $this->response->show(400, '班级信息ID错误');
        }
        // 模拟数据
        /*$cs_id = 10; // 课程ID
        $start_time = '10:55'; // 上课时间
        $end_time = '12:00'; // 上课时间
        $ro_id = 1; // 教室
        $tc_id = 2221; // 授课老师
        $zj_id = 4948; // 辅班老师
        $max_stu = 12; // 班级容量
        $class_rule_json = '[
            {"ptdate":"2020-08-06","id":"85"},
            {"ptdate":"2020-08-07","id":"86"},
            {"ptdate":"2020-08-08","id":"87"},
            {"ptdate":"2020-08-09","id":"88"}
        ]';*/
        $start_time = I('post.start_time');
        $end_time = I('post.end_time');
        $ro_id = I('post.ro_id', 0, 'intval');
        $tc_id = I('post.tc_id', 0, 'intval');
        $zj_id = I('post.zj_id', 0, 'intval');
        $max_stu = I('post.max_stu', 0, 'intval');
        // 排课数据 接收过来是数组 包裹着班级选择节数对应数量的日期
        $class_rule_json = I('post.class_rule', '', 'trim');
        $class_rule = json_decode($class_rule_json, true);
        if(empty($class_rule)){
            $this->response->show(400, '请传入要变动的信息参数');
        }
        $old_classrule_ids = array_column($class_rule, 'id');
        // 根据要操作的排课ID查出班级的所有排课 用于日志记录 未修改前
        $old_classrule = $this->jwClassRule->where([
            'id'      => ['in', $old_classrule_ids],
            'classid' => $classid
        ])->select();
        // 检测授课老师和辅班老师是否一致
        if ($tc_id == $zj_id) {
            $this->response->show(400, '辅班老师和授课老师不能选择同一个教室');
        }
        // 验证上课教室 上课老师 辅班老师是否被占用
        // 抱歉，X月X日的X点的课程老师和其他课程冲突
        // 抱歉，X月X日的X点的课程教室和其他课程冲突
        foreach ($class_rule as $v) {
            // 检测已有考勤的课程的日期和时间 如果传过来的值和之前的没有变动 说明没有更改，反之其他问题
            $attendance = $this->jwStudentReg->where([
                'ru_id' => $v['id'],
                'type'  => 'QD'
            ])->count();
            $old_rule = $this->jwClassRule->find($v['id']);
            if ($attendance > 0 && ($v['ptdate'] != $old_rule['ptdate'] || $old_rule['start_time'] != $start_time || $old_rule['end_time'] != $end_time)) {
                $message = "抱歉，{$v['ptdate']}的{$start_time}的课程，已有考勤学员";
                $this->response->show(400, $message);
            }
            // 验证上课教室 上课老师 辅班老师是否被占用
            $occupy_room = $this->jwClassRule
                ->checkOccupy($this->org_id,$ro_id, $v['ptdate'], $start_time, $end_time, JwClassEnum::OCCUPY_ROOM, $v['id']);
            if (!empty($occupy_room)) {
                $message = "抱歉，{$v['ptdate']}的{$start_time}的课程教室和其他课程冲突";
                $this->response->show(400, $message);
            }
            $occupy_tech = $this->jwClassRule
                ->checkOccupy($this->org_id,$tc_id, $v['ptdate'], $start_time, $end_time, JwClassEnum::OCCUPY_TECH, $v['id']);
            if (!empty($occupy_tech)) {
                $message = "抱歉，{$v['ptdate']}的{$start_time}的授课老师和其他课程冲突";
                $this->response->show(400, $message);
            }
            $occupy_zjch = $this->jwClassRule
                ->checkOccupy($this->org_id,$zj_id, $v['ptdate'], $start_time, $end_time, JwClassEnum::OCCUPY_ZJCH, $v['id']);
            if (!empty($occupy_zjch)) {
                $message = "抱歉，{$v['ptdate']}的{$start_time}的辅班老师和其他课程冲突";
                $this->response->show(400, $message);
            }
            // 组装排课数据
            $class_rule_data['week_num'] = $this->jwClassRule->getNowWeeksByTime($v['ptdate']);
            $class_rule_data['ptdate'] = $v['ptdate'];
            $class_rule_data['week'] = date("w", strtotime($v['ptdate']));
            $class_rule_data['id'] = $v['id'];
            if(!empty($start_time)){
                $class_rule_data['start_time'] = $start_time;
            }
            if(!empty($end_time)){
                $class_rule_data['end_time'] = $end_time;
                $class_rule_data['strtime'] = $start_time . '-' . $end_time;
            }
            if(!empty($ro_id)){
                $class_rule_data['ro_id'] = $ro_id;
            }
            if(!empty($zj_id)){
                $class_rule_data['zj_id'] = $zj_id;
            }
            if(!empty($max_stu)){
                $class_rule_data['max_stu'] = $max_stu;
            }
            M()->startTrans();
            $jwclassrule_res = $this->jwClassRule->save($class_rule_data);
            if ($jwclassrule_res) {
                $code = 200;
                $msg = '更新成功';
                M()->commit();
            } else {
                $code = 400;
                $msg = '更新失败，事务回滚';
                M()->rollback();
                break;
            }
        }
        // 组装创建记录数据
        // 插入前
        $befor_ptdate_str = '';
        $befor_pttime_str = '';
        $befor_skroom_str = '';
        $befor_weeksk_str = '';
        $befor_sktech_str = '';
        $befor_zjtech_str = '';
        $befor_status_str = '';
        $class_room = $this->jwClassRoom->getNameByOrgid($this->org_id);
        $member = $this->member->getNicknameByOrgid($this->org_id);
        foreach ($old_classrule as $key => $val) {
            $befor_ptdate_str .= $val['ptdate'] . '、';
            $befor_pttime_str .= $val['start_time'] . '-' . $val['end_time'] . '、';
            $befor_skroom_str .= $class_room[$val['ro_id']] . '、';
            $befor_weeksk_str .= JwClassEnum::WEEK[$val['week']] . '、';
            $befor_sktech_str .= $member[$val['tc_id']] . '、';
            $befor_zjtech_str .= $member[$val['zj_id']] . '、';
            $befor_status_str .= JwClassEnum::STATUS[$val['status']] . '、';
        }
        $befor['ptdate'] = rtrim($befor_ptdate_str, '、');
        $befor['sktime'] = rtrim($befor_pttime_str, '、');
        $befor['skroom'] = rtrim($befor_skroom_str, '、');
        $befor['weeksk'] = rtrim($befor_weeksk_str, '、');
        $befor['sktech'] = rtrim($befor_sktech_str, '、');
        $befor['zjtech'] = rtrim($befor_zjtech_str, '、');
        $befor['status'] = rtrim($befor_status_str, '、');
        // 插入后
        // 根据要操作的排课ID查出班级的所有排课 用于日志记录 修改后
        $new_classrule = $this->jwClassRule->where([
            'id'      => ['in', $old_classrule_ids],
            'classid' => $classid
        ])->select();
        $after_ptdate_str = '';
        $after_pttime_str = '';
        $after_skroom_str = '';
        $after_weeksk_str = '';
        $after_sktech_str = '';
        $after_zjtech_str = '';
        $after_status_str = '';
        foreach ($new_classrule as $key => $val) {
            $after_ptdate_str .= $val['ptdate'] . '、';
            $after_pttime_str .= $val['start_time'] . '-' . $val['end_time'] . '、';
            $after_skroom_str .= $class_room[$val['ro_id']] . '、';
            $after_weeksk_str .= JwClassEnum::WEEK[$val['week']] . '、';
            $after_sktech_str .= $member[$val['tc_id']] . '、';
            $after_zjtech_str .= $member[$val['zj_id']] . '、';
            $after_status_str .= JwClassEnum::STATUS[$val['status']] . '、';
        }
        $after['ptdate'] = rtrim($after_ptdate_str, '、');
        $after['sktime'] = rtrim($after_pttime_str, '、');
        $after['skroom'] = rtrim($after_skroom_str, '、');
        $after['weeksk'] = rtrim($after_weeksk_str, '、');
        $after['sktech'] = rtrim($after_sktech_str, '、');
        $after['zjtech'] = rtrim($after_zjtech_str, '、');
        $after['status'] = rtrim($after_status_str, '、');
        $class_rule_json = [
            'ruid_list' => $old_classrule_ids,
            'befor'     => $befor,
            'after'     => $after
        ];
        $log_data = [
            'type'     => '修改',
            'title'    => '班级变更记录',
            'area'     => 'JWBJBG',
            'username' => session('user_auth')['nickname'],
            'json'     => json_encode($class_rule_json),
            'classid'  => $classid,
            'org_id'   => $this->org_id,
        ];
        $this->jwLog->add($log_data);
        $this->response->show($code, $msg);
    }

    /**
     * 班级操作记录
     * JWBJTJ 教务班级添加
     * JWBJBG 教务班级变动
     * JWBJTQ 教务班级停启
     * JWBJJZBBKG 教务家长报班开关
     * @param int $classid 班级ID
     */
    public function class_operation_log()
    {
        $classid = I('post.classid', 0, 'intval');
        $class = $this->jwClass->find($classid);
        if (!$classid || empty($class)) {
            $this->response->show(400, '班级信息ID错误');
        }
        $stage = $this->jwCourseStage
            ->field('id, title, start_mouth, end_mouth')
            ->find($class['cs_id']);
        $jw_log = $this->jwLog->where([
            'area'    => ['in', ['JWBJTJ', 'JWBJBG', 'JWBJTQ', 'JWBJJZBBKG']],
            'classid' => $classid
        ])->order('ctime desc')->select();
        $operation_data = [];
        foreach ($jw_log as $key => $val) {
            if (!empty($val['json'])) {
                $val_json = json_decode($val['json'], true);
                if (isset($val_json['ruid_list'])) unset($val_json['ruid_list']);
                $val_json['type'] = $val['area'];
                $val_json['title'] = $val['title'];
                $val_json['ctime'] = $val['ctime'];
                $val_json['username'] = $val['username'];
                $operation_data[] = $val_json;
            }
        }
        $stage['classid'] = $classid;
        $stage['operation_data'] = $operation_data;
        $this->response->show(200, 'success', $stage);
    }

    /**
     * 日期对调
     * 选择日期查排课列表
     *
     * 前端第一次选择前者日期时需要判断返回数据是否为空
     * 前端来判断两次请求日期是否一致，是就抛出提示
     * 要调换的日期必须有数据
     * 不然重新请求接口直到有数据为止
     *
     * 后者日期不检查，只检查前者日期
     * 前者后者统一访问此接口
     *
     * QD 已考勤
     * @param string $date 日期格式: 2020-08-06
     */
    public function date_swich_query()
    {
        $date = I('request.date', '', 'trim');
        if (empty($date) || strlen($date) != 10 || false === strpos($date, '-')) {
            $this->response->show(400, '日期为空或格式错误');
        }
        // 检测日期必须是大于等于当前日期
        if (strtotime($date) <= strtotime(date('Y-m-d'))) {
            $this->response->show(400, '所选日期必须大于当前日期');
        }
        $class_rule = $this->jwClassRule->where([
            'ptdate' => $date,
            'org_id' => $this->org_id
        ])->field('id, cs_id, classid')->select();
        // 获取班级ID
        $classids = array_column($class_rule, 'classid', 'classid');
        if (!empty($classids)) {
            // 检测是否有学员已经考勤
            $student_reg = $this->jwStudentReg->where([
                'type'    => 'QD',
                'classid' => ['in', $classids]
            ])->count();
            if ($student_reg > 0) {
                $this->response->show(400, '抱歉，该日的课程中有学员已经考勤，不支持对调');
            }
            // 查询出课程
            $stage = $this->jwCourseStage->where([
                'org_id' => $this->org_id
            ])->getField('id, title');
            foreach ($class_rule as $key => $val) {
                $class_rule[$key]['title'] = $stage[$val['cs_id']];
            }
        }
        $this->response->show(200, 'success', $class_rule);
    }

    /**
     * 日期对调
     * 课程日期和周几互换
     * 某天的和未来的课程互换(日期)
     * @param string $go_date 前者日期 格式:2020-08-05
     * @param string $to_date 后者日期 格式:2020-08-10
     */
    public function date_swich_update()
    {
        $go_date = I('post.go_date', '', 'trim');
        $to_date = I('post.to_date', '', 'trim');
        if (empty($go_date)
            || empty($to_date)
            || strlen($go_date) != 10
            || strlen($to_date) != 10
            || false === strpos($go_date, '-')
            || false === strpos($to_date, '-')) {
            $this->response->show(400, '日期为空或格式错误');
        }
        if($go_date==$to_date){
            $this->response->show(400, '请选择不同日期进行调换');
        }
        // 检测日期必须是大于等于当前日期
        if (strtotime($go_date) <= strtotime(date('Y-m-d')) || strtotime($to_date) <= strtotime(date('Y-m-d'))) {
            $this->response->show(400, '所选日期必须大于当前日期');
        }
        $rules_go = $this->jwClassRule->where([
            'ptdate' => $go_date,
            'org_id' => $this->org_id
        ])->field('id,classid')->select();
        $rules_to = $this->jwClassRule->where([
            'ptdate' => $to_date,
            'org_id' => $this->org_id
        ])->field('id,classid')->select();
        
        $classrule_ids_go = [];
        $classrule_ids_to = [];
        $class_ids = []; //所有的班级id
        
        foreach($rules_go as $item){
            $classrule_ids_go[] = $item['id'];
            $class_ids[] = $item['classid'];
        }
        foreach($rules_to as $item){
            $classrule_ids_to[] = $item['id'];
            $class_ids[] = $item['classid'];
        }
        $class_ids = array_unique($class_ids);

        // 检测前者是否有数据 后者不需要检测
        if (empty($rules_go) && empty($rules_to)) {
            $this->response->show(400, '日期内没有排课，无需调换');
        }
        // 检测是否有学员已经考勤
        $hadQd = $this->jwStudentReg->where([
            'type'    => 'QD',
            'ru_id' => ['in',array_merge($classrule_ids_go,$classrule_ids_to)]
        ])->getField('id');
        if ($hadQd) {
            $this->response->show(400, '抱歉，课程中有学员已经考勤，不支持对调');
        }

        //判断学员合同是否有对调遇到超出合同范围情况
        $check1 =  $this->jwClassRule->checkChangeRuleDateConts($this->org_id,$classrule_ids_go,$to_date);
        $check2 = $this->jwClassRule->checkChangeRuleDateConts($this->org_id,$classrule_ids_to,$go_date);
        
        $check = array_merge($check1,$check2);
        if($check){
            $this->response->show(401, '部分条件不支持',$check);
        }

        $go_week = date("w", strtotime($go_date));
        $to_week = date("w", strtotime($to_date));
        $model = M();
        $model->startTrans();
        $rollback = false;
        if($classrule_ids_go){
            $go_res = $this->jwClassRule->where([
                'id' => ['in', $classrule_ids_go]
            ])->setField(['ptdate' => $to_date, 'week' => $to_week]);
            if($go_res===false){
                $rollback = true;
                $model->rollback();
            }
        }
        if ($classrule_ids_to) {
            $to_res = $this->jwClassRule->where([
                'id' => ['in', $classrule_ids_to]
            ])->setField(['ptdate' => $go_date, 'week' => $go_week]);
            if($to_res===false){
                $rollback = true;
                $model->rollback();
            }
        }
        if($rollback){
            $this->response->show(400, '日期对调失败');
        }else{
            $model->commit();
            //排课的周次
            foreach($class_ids as $classid){
                $model->execute("SET @rownum = 0");
                $model->execute("update wzj_home_jw_class_rule set week_num=(@rownum := @rownum + 1) where classid='{$classid}' and org_id='{$this->org_id}' order by ptdate,start_time");
            }
            $class_ids_str = implode(',',$class_ids);
            $model->execute("update wzj_home_jw_class c ,(SELECT classid,GROUP_CONCAT( distinct `week`) as weeks FROM `wzj_home_jw_class_rule` 
                where  org_id='{$this->org_id}' and classid in ($class_ids_str) GROUP by `classid`) as tmp set c.`week`=tmp.`weeks` where c.id=tmp.classid");
            $this->response->show(200, '日期对调成功');
        }
    }

    /**
     * 排课记录
     */
    public function arranging_log(){
        //选择框查询
        $type = I('request.type');
        $type_val = I('request.type_val', '', 'trim');

        if ($type > 6) {
            $this->response->show(400, '请选择准确的查询条件');
        }

        $where = array(); //查询条件
        switch ($type) {
            case 1:
                $where['stage.title'] = $type_val;
                break;
            case 2:
                $where['rule.week'] = $type_val;
                break;
            case 3:
                $where['room.name'] = $type_val;
                break;
            case 4:
                $where['tc.nickname'] = ['like', '%' . $type_val . '%'];
                break;
            case 5:
                $where['zj.nickname'] = ['like', '%' . $type_val . '%'];
                break;
//            case 6:
//                $where['rule.parentsign'] = $type_val;
//                break;
        }

        //日期查询条件
        $sdate = I('request.sdate', '', 'trim');
        $edate = I('request.edate', '', 'trim');
        if (empty($sdate) && empty($edate)) {
            //日期范围默认今天
            $sdate = date("Y-m-d");
            $edate = date("Y-m-d");

        }
        $where['rule.ptdate'] = ['between', [$sdate, $edate]];
        //日期查询条件 -end

        $where['rule.org_id'] = $this->org_id;
        //获取当前where条件下的总条数
        $total = $this->jwClassRule->getListCount($where);

        list($limit, $size, $total_page) = paGing($this->size, $total);
        //获取当前机构下的全部班级列表信息
        $RuleInfo = $this->jwClassRule->getRuleList($where,$limit,$size);

        foreach ($RuleInfo as $k => $v){
            $ru_id = $v['id'];
            //正式位
            $where_zs['type'] = ['in',['YY','QJ','QD','QQD']];
            $studentinfo = $this->jwStudentReg->getStudentInfo($ru_id,$where_zs);
            $RuleInfo[$k]['zs'] = $studentinfo;
            //试听位
            $where_st['type'] = 'ST';
            $studentinfo = $this->jwStudentReg->getStudentInfo($ru_id,$where_st);
            $RuleInfo[$k]['st'] = $studentinfo;
            //未考勤
            $where_wkq['type'] = ['in',['YY','QQD']];
            $where_wkq['reg_type'] = 0;
            $studentinfo = $this->jwStudentReg->getStudentInfo($ru_id,$where_wkq);
            $RuleInfo[$k]['wkq'] = $studentinfo;
            //出勤
            $where_cq['type'] = 'QD';
            $where_cq['sign_type'] = 1;
            $studentinfo = $this->jwStudentReg->getStudentInfo($ru_id,$where_cq);
            $RuleInfo[$k]['cq'] = $studentinfo;
            //请假
            $where_qj['type'] = 'QJ';
            $studentinfo = $this->jwStudentReg->getStudentInfo($ru_id,$where_qj);
            $RuleInfo[$k]['qj'] = $studentinfo;
            //旷课
            $where_gk['sign_type'] = 3;
            $studentinfo = $this->jwStudentReg->getStudentInfo($ru_id,$where_gk);
            $RuleInfo[$k]['gk'] = $studentinfo;
            //补课
            $where_bk['reg_type'] = 1;
            $studentinfo = $this->jwStudentReg->getStudentInfo($ru_id,$where_bk);
            $RuleInfo[$k]['bk'] = $studentinfo;
        }
        $return_data = array(
            'total' => $total,
            'total_page' => $total_page,
            'data' => $RuleInfo
        );

        $this->response->show(200, 'success', $return_data);
    }

    /**
     * 班级课表
     */
    public function class_timetable_log(){
        $where = array();//查询条件
        $where['rule.org_id'] = $this->org_id;

        $now_year_week = $this->jwClassRule->getNowYearAndNowWeek();
        //获取当前是时间  例：year(2020) week(第31周)
        $year = I('request.year',$now_year_week['now_year']);
        $week = I('request.week',$now_year_week['now_week']);

        $course_name  = I('request.c_name','');//课程名称
        $teacher_name = I('request.t_name','');//老师名称

        //今日统计
        $DataToday = array();
        $today = date('Y-m-d',time());
        $condtion['rule.ptdate'] = $today;
        $condtion['rule.org'] = $this->org_id;
        $DataTodayInfo = $this->jwClassRule->getClassNumberInfo($where);
        $DataToday = $this->today_info($where,$DataTodayInfo);

        //本周统计
        $dateBetween = $this->jwClassRule->getTimeBucket($year,$week);
        $start_time = $dateBetween['start_time'];
        $end_time = $dateBetween['end_time'];
        $where['rule.ptdate'] = ['between', [$start_time, $end_time]];
        $DataWeekInfo = $this->jwClassRule->getClassNumberInfo($where);
        $DataWeek = $this->today_info($where,$DataTodayInfo);

        if ($teacher_name){
            $as['tc.nickname'] = ['like', '%' . $teacher_name . '%'];
            $as['zj.nickname'] = ['like', '%' . $teacher_name . '%'];
            $as['_logic'] = 'or';
            $where['_complex'] = $as;
        }
        if ($course_name){
            $where['stage.title'] = $course_name;
        }
        //获取班级课表信息
        $Classinfo = $this->jwClassRule->getClasstableInfo($where);
        unset($where['_complex']);
        unset($where['stage.title']);

        //获取班级正式位信息
        $TeacherInfo = array();
        foreach ($Classinfo as $k => $v){
            $ru_id = $v['id'];
            //正式位
            $where_zs['type'] = ['in',['YY','QJ','QD','QQD']];
            $studentinfo = $this->jwStudentReg->getStudentInfo($ru_id,$where_zs);
            $Classinfo[$k]['zs'] = $studentinfo;
            //试听
            $where_st['type'] = 'ST';
            $studentinfo = $this->jwStudentReg->getStudentInfo($ru_id,$where_st);
            $Classinfo[$k]['st'] = $studentinfo;
        }

        //获取当前数据内所有的老师名称
        $TeacherName = $this->jwClassRule->getTearcherName($where);
        //获取当前数据内所有的课程名称
        $CourseName = $this->jwClassRule->getCourseName($where);
        //获取当前时间段内所有班级名称
        $ClassName = $this->jwClassRule->getClassName($where);

        //获取时间段
        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        $i = 0;
        while($start_time <= $end_time){
            $arr[$i] = date('Y-m-d',$start_time);
            $start_time = strtotime('+1 day',$start_time);
            $i++;
        }

        //返回规定的数据格式
        $data = array();
        if($Classinfo && $ClassName && $arr){
            for ($i = 0;$i <count($arr);$i++){
                $arr_time = $arr[$i];
                for ($j = 0;$j<count($ClassName);$j++){
                    $class_time = $ClassName[$j]['ptdate'];
                    if ($arr_time == $class_time){
                        $class_name = $ClassName[$j]['name'];
                        $data[$arr_time][$class_name] = $class_name;
                    }
                    if (!isset($data[$arr_time])){
                        $data[$arr_time] = array();
                    }
                }
            }
            $dataInfo = array();
            foreach ($data as $k => $v){
                $data_time = $k;
                if (is_array($v)){
                    foreach ($v as $key => $value){
                        $data_name = $value;
                        foreach ($Classinfo as $ke => $va){
                            if ($data_time == $va['ptdate'] && $data_name == $va['name']){
                                $dataInfo[$data_time][$data_name][] = $va;
                            }
                        }
                    }
                    if (!isset($dataInfo[$data_time])){
                        $dataInfo[$data_time] = array();
                    }
                }
            }
        }

        if (empty($dataInfo)){
            for ($i = 0;$i <count($arr);$i++) {
                $dataInfo[$arr[$i]] = array();
            }
        }

        $return_data = array(
            'course' => $CourseName,
            'teacher' => $TeacherName,
            'dataToday' => $DataToday,
            'dataWeek'  => $DataWeek,
            'dataInfo'  => $dataInfo
        );
        $this->response->show(200, 'success', $return_data);

    }

    /**
     * 获取班级课表统计的人数
     */
    protected function today_info($where = array(),$DataTodayInfo = array()){
        $DataToday['arrive'] = 0;
        $DataToday['come'] = 0;
        $DataToday['lack'] = 0;
        $DataToday['audition'] = 0;
        $DataToday['visit'] = 0;
        $DataToday['nodiligent'] = 0;
        foreach ($DataTodayInfo as $k => $v){

            //应到人次:未考勤+ 出勤 + 请假 + 旷课 + 补课
            if(in_array($v["type"],array('YY','QD','QQD','QJ'))){
                $DataToday['arrive'] += 1;
            }
            //实到人数:出勤 + 补课
            if(($v['type'] == 'QD' && $v['sign_type'] == 1) || $v['reg_type'] == 1){
                $DataToday['come'] += 1;
            }

            //缺勤人数:请假 + 旷课
            if($v['type'] == 'QJ' || $v['sign_type'] == 3){
                $DataToday['lack'] += 1;
            }
            //试听人数:试听位人数
            if($v['type'] == 'ST'){
                $DataToday['audition'] += 1;
            }
            //试听已上:试听位已到访人数
            if($v['type'] == 'ST' && $v['isvisit'] == 1){
                $DataToday['visit'] += 1;
            }
            //未考勤人数:正式位未考勤人数
            if(in_array($v['type'],array('YY','QQD')) && $v['sign_type'] == 1){
                $DataToday['nodiligent'] += 1;
            }
        }
        return $DataToday;
    }

    /**
     * 花名册右侧滑块
     */
    public function ClassRosterInfo(){
        $class_id = I('post.classid', 0, 'intval');
        $sdate = I('post.sdate',0);
        $edate = I('post.edate',0);
        $org_id = $this->org_id;
        if ($class_id  && $sdate && $edate){
            $cwhere['rule.org_id'] = $org_id;
            $cwhere['rule.classid'] = $class_id;
            $cwhere['rule.ptdate'] = ['between', [$sdate, $edate]];
            //获取当前课程下所有的学员信息
            $total = $this->jwStudentReg->getcoursePidcount($cwhere);
            list($limit, $size, $total_page) = paGing(20, $total);

            $studentInfo = $this->jwStudentReg->getcoursePidInfo($cwhere,$limit,$size);

            $twhere['org_id'] = $org_id;
            $twhere['classid'] = $class_id;
            $twhere['ptdate'] = ['between', [$sdate, $edate]];
            //获取当前班级下的排课的日期
            $timedata = M('jwClassRule')->where($twhere)
                ->field('id,ptdate,week')
                ->group('ptdate')
                ->select();

            $today = date('Y-m-d');
            $aboutclass_msg = '';
            $contractData = array();

            foreach ($studentInfo as $k => $v){
                /*获取报课状态
                     开课中：     当前日期 >= 预约的第一节课上课日期
                                 当前日期 <=预约的最后一节课上课日期

                     未开课：     当前日期 <预约的第一节课上课日期

                     已结课：     当前日期>预约的最后一节课的日期  */
                if ($today >= $v['smallptdate'] && $today <= $v['bigptdate']) {
                    $aboutclass_msg = '开课中';
                }
                if ($today < $v['smallptdate']) {
                    $aboutclass_msg = '未开课';
                }
                if ($today > $v['bigptdate']) {
                    $aboutclass_msg = '已结课';
                }
                $studentInfo[$k]['classSatus'] = $aboutclass_msg;
                //获取当前学员下的合同的信息
                $studentbid = $v['bid'];
                $studentid = $v['id'];
                $contractInfo = M('JwContract')->where("bid = $studentbid")->select();

                if (empty($contractInfo)){
                    $studentInfo[$k]['contractInfo'][] = array();
                }else{
                    foreach ($contractInfo as $key => $value){
                        $contractData['title'] = $value['title'];
                        $contractData['package_type'] = $value['package_type'];
                        $contractData['hours'] = $value['hours'];
                        $contractData['start'] = $value['start_date'];
                        $contractData['end'] = $value['end_date'];
                        $contractData['status'] = $value['status'];
                        $studentInfo[$k]['contractInfo'][] = $contractData;
                    }
                }
                //获取出勤的数据
                $swhere['bid'] = $studentbid;
                $swhere['ru_id'] = $studentid;
                $swhere['type'] = QD;
                $swhere['sign_type'] = 1;
                $chuqin = $this->jwStudentReg->getStudentInfo('',$swhere);

                $studentInfo[$k]['cq'] =$chuqin;
                //获取当前学员的其他的班级
                $xwhere['reg.bid']= $studentbid;
                $xwhere['reg.type'] = ['in',['YY','QD','QJ','QQD','TS']];
                $stage_info = $this->jwStudentReg->getStageNameInfo($xwhere);
                $stageInfo = '';
                foreach ($stage_info as $ke => $va){
                    $title = $va['title'];
                    $stageInfo .= $title.',';
                }
                $stageInfo  = substr($stageInfo, 0, -1); // 利用字符串截取函数消除最后一个逗号
                $studentInfo[$k]['stageInfo'] = $stageInfo;
                $tdata = '';

                //查看当前学员是否已经报
                foreach ($timedata as $va){
                    $fwhere['rule.ptdate'] = $va['ptdate'];
                    $fwhere['reg.bid'] = $studentbid;
                    $timeInfo = M('jwStudentReg')->alias('reg')
                        ->join('wzj_home_jw_class_rule  rule ON reg.ru_id = rule.id')
                        ->field('reg.id,reg.bid,reg.type,reg.reg_type,reg.sign_type,reg.can_buke')
                        ->where($fwhere)
                        ->find();
//                     var_dump($timeInfo['sign_type']);die;
                    $reg_msg = '未报班';
                    if ($timeInfo){
                        if ($timeInfo['type'] == 'QD' && $timeInfo['sign_type'] == 1){
                            $reg_msg = '出勤';
                        }
                        if ($timeInfo['type'] == 'QJ'){
                            $reg_msg = '请假';
                        }
                        if ($timeInfo['sign_type'] == 3){
                            $reg_msg = '旷课';
                        }
                        if ($timeInfo['sign_type'] == 3 && $timeInfo['can_buke'] == 0){
                            $reg_msg = '旷课不可补课';
                        }
                        if ($timeInfo['sign_type'] == 3 && $timeInfo['can_buke'] == 1){
                            $reg_msg = '旷课可补课';
                        }
                    }
                    $data[$va['ptdate']] = $reg_msg;
                    $studentInfo[$k]['timeInfo'] = $data;
                }

            }


        }else{
            $this->response->show(400, '参数异常');
        }

        $return_data = array(
            'total' => $total,
            'total_page' => $total_page,
            'time' => $timedata,
            'classInfo' => $studentInfo
        );
        $this->response->show(200, 'success', $return_data);

    }

    /**
     * 排课记录 - 操作记录
     * $titleid 操作类型id
     * $studentname 学员姓名
     * $phone 手机号
     */
    public function class_rule_log(){
        $org_id = $this->org_id;
        $where['log.ru_id'] = I('get.ru_id',0,'intval');
        $where['log.org_id'] = $org_id;

        if (empty($where['log.ru_id'])){
            $this->response->show(400, '参数异常');
        }
        $titleid = I('post.titleid',0,'intval');

        $studentname = I('post.studentname','');

        $phone = I('post.phone','');
        if ($studentname){
           $where['xs.studentname'] = ['like', '%' . $studentname . '%'];
        }
        if ($phone){
           $where['xs.phone'] = ['like', '%' . $phone . '%'];
        }


        $Info = array(
            'zs' => array(
                '1' => '报班',
                '2' => '正式位签到(已到)',
                '3' => '正式位签到(旷课)',
                '4' => '正式位签到(撤销)',
                '5' => '正式位学员(请假)',
                '6' => '正式位学员(取消)',
                '7' => '正式位学员(补课)',
                '8' => '正式位学员(取消补课)',
                '9' => '课程评价',
                '10'=> '家长回评',
            ),
            'st' => array(
                '11' => '试听位(添加)',
                '12' => '试听位(取消)'
            ),
        );
        if ($titleid){
            $title =  $Info['zs'][$titleid];
            if (empty($title)){
                $title = $Info['st'][$titleid];
            }
            if ($title){
                $where['log.title'] = $title;
            }

        }

        $total = M('JwLog')->alias('log')
            ->join('wzj_home_xs_students xs ON log.bid = xs.bid','left')
            ->where($where)
            ->count();

        list($limit, $size, $total_page) = paGing($this->size, $total);

        $Loginfo = M('JwLog')->alias('log')
            ->join('wzj_home_xs_students xs ON log.bid = xs.bid','left')
            ->field('log.id,log.type,log.title,log.area,log.ctime,log.content,log.username')
            ->limit($limit,$size)
            ->order('log.ctime desc')
            ->where($where)
            ->select();

        $return_data = array(
            'type' => $Info,
            'total' => $total,
            'total_page' => $total_page,
            'loginfo' => $Loginfo,
        );

        $this->response->show(200, 'success', $return_data);
    }
}