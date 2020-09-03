
<<<<<<< HEAD
<?php
/**
 * 微早教SCRM JwClassRule.class.php文件
 * ----------------------------------------------
 * 排课控制器1
 * ==============================================
 * 版权所有 2020 http://www.weizaojiao.cn
 * ==============================================
 * @date 2020/7/28 14:11
 * @author: 周章亮（zhouzhangliang@weizaojiao.cn）
 * @version: 1.0
 */

namespace Business\Controller;

use Org\Util\Response;
use Business\enum\JwClassEnum;
use Business\enum\JwContractEnum;
use Business\enum\BusinessBabyEnum;

class JwClassRuleController extends IndexController
{
    protected $response, $member,
        $jwClass, $jwClassRule, $jwStudentReg,
        $jwCourseStage, $businessMapcase, $businessBabyinfo,
        $businessParentinfo, $jwContract, $jwClassRoom,
        $jwCourse;

    public function __construct()
    {
        parent::__construct();
        $this->response = Response::start();
        $this->member = D('member');
        $this->jwClass = D('JwClass');
        $this->jwCourse = D('JwCourse');
        $this->jwContract = D('JwContract');
        $this->jwClassRule = D('JwClassRule');
        $this->jwClassRoom = D('JwClassRoom');
        $this->jwStudentReg = D('jwStudentReg');
        $this->jwCourseStage = D('JwCourseStage');
        $this->businessMapcase = D('BusinessMapcase');
        $this->businessBabyinfo = D('BusinessBabyinfo');
        $this->businessParentinfo = D('BusinessParentinfo');
    }

    /**
     * 报班约课课列表
     * @param int $classid 班级ID
     */
    public function about_class_head()
    {
        $classid = I('request.classid', 0, 'intval');
        $class = $this->jwClass->getRuleinfoByClassid($classid);
        if (empty($classid) || empty($class)) {
            $this->response->show(400, '没有此数据或班级已停用');
        }
        // 查出排课列表
        $where = [
            'jcr.org_id'  => $this->org_id,
            'jcr.classid' => $classid
        ];
        $rule_list = $this->jwClassRule
            ->alias('jcr')
            ->field('jcr.id, jcr.ptdate, jcr.start_time, jcr.max_stu, count(jsr.id) as formal, jcr.status')
            ->join('wzj_home_jw_student_reg jsr ON jcr.id = jsr.ru_id AND jsr.type in ("QD","QQD","YY","QJ") AND jsr.status = 1', 'left')
            ->where($where)
            ->group('jcr.id')
            ->order('jcr.ptdate asc')
            ->select();
        foreach ($rule_list as $key => $val) {
            // 历史日期是否可选标志
            $checked = 1;
            if ($val['ptdate'] < date('Y-m-d') || $val['status'] != 1) {
                $checked = 0;
            }
            $rule_list[$key]['checked'] = $checked;
        }
        $class['start_end_mouth'] = monthToAge($class['start_mouth']).'-'.monthToAge($class['end_mouth']);
        $class['class_rule'] = $rule_list;
        $this->response->show(200, 'success', $class);
    }

    /**
     * 报班约课
     * @param int $classid 班级ID
     */
    public function about_class()
    {
        $classid = I('request.classid', 0, 'intval');
        $class = $this->jwClass->getRuleinfoByClassid($classid);
        $ptdate = $this->jwClassRule->where([
            'classid' => $class['id'],
            'status' => 1,
            'org_id' => $this->org_id
        ])->getField('ptdate', true);
        if (empty($classid) || empty($class)) {
            $this->response->show(400, '没有此数据或班级已停用');
        }
        // 没有筛选时默认取出签约共享学员 包含该课程
        // 如果首次进来筛选出课程月龄对应学龄的默认学员

=======
 public function about_class()
    {
        $classid = I('request.classid', 0, 'intval');
        $class = $this->jwClass->getRuleinfoByClassid($classid);
        $ptdate = $this->jwClassRule->where([
            'classid' => $class['id'],
            'status' => 1,
            'org_id' => $this->org_id
        ])->getField('ptdate', true);
        if (empty($classid) || empty($class)) {
            $this->response->show(400, '没有此数据或班级已停用');
        }
        // 没有筛选时默认取出签约共享学员 包含该课程
        // 如果首次进来筛选出课程月龄对应学龄的默认学员

>>>>>>> ce3
        // 学员姓名 学员昵称 家长手机 所属顾问 学员学龄
        // 根据上述条件查出学员ID
        // 根据学员ID查出当前的合同信息
        // 签约学员
        $today = date('Y-m-d');
        // $where['mapcase.identity'] = ['in', ['SS', 'BB']];
        $where['mapcase.identity'] = ['NEQ', 'XX'];
        // 学员姓名
        $studentname = I('request.studentname', '', 'trim');
        if (!empty($studentname)) {
            $where['mapcase.studentname'] = ['like', '%' . $studentname . '%'];
        }
        // 学员昵称
        $babyname = I('request.babyname', '', 'trim');
        if (!empty($babyname)) {
            $where['babyinfo.babyname'] = ['like', '%' . $babyname . '%'];
        }
        // 家长手机
        $phone = I('request.phone', '', 'trim');
        if (!empty($phone)) {
            $where['parentinfo.phone'] = ['like', '%' . $phone . '%'];
        }
        // 月龄 不是宝宝生日，是个年龄区间 类似：1_1-1_2 表示1岁1月到1岁2月的学员
        $month_age = I('request.month_age', '', 'trim');
        if (!empty($month_age) && $month_age != '_-_') {
            $age_range_str_arr = explode('-',$month_age);
            $months_range_arr = array_map(function($v){
                $ageArr = explode('_',$v);
                return $ageArr[0]*12+$ageArr[1];
            },$age_range_str_arr);
            $start_month = $months_range_arr[0];
            $end_month = $months_range_arr[1] + 1;
        }
        // 所属顾问
        $sale_id = I('request.sale_id', 0, 'intval');
        if (!empty($sale_id)) {
            $where['mapcase.sale_id'] = $sale_id;
        }
        // 取出签约共享人员
        $qianyue = M('business_mapcase')->where([
            'identity' => 'SS',
            'status'   => 1,
            'org_id'   => $this->org_id
        ])->select();
        $qianyueids = array_column($qianyue, 'bid');
        if (!empty($qianyueids)) {
            $share = M('jw_contract_share')->where([
                'bid' => ['in', $qianyueids]
            ])->getField('share_bid', true);
            $xueyuanids = array_merge($qianyueids, $share);
            $where['contract.bid|share.share_bid'] = ['in', $xueyuanids];
        }
        $join = '';
        // 没有搜索条件时的默认查询
        if (empty($studentname) && empty($babyname) && empty($phone) && empty($sale_id) && (empty($month_age) || $month_age == '_-_')) {
            // 合同包含该课阶分类
            $join = "AND (find_in_set({$class['cr_id']}, contract.cr_id) or contract.cr_id = '-1')";
            $start_month = $class['start_mouth'];
            $end_month = $class['end_mouth'] + 1;
        }
        //根据年龄推算出生日
        foreach($ptdate as $v){
            $maxBirthday[] = $this->jwStudentReg->getBirthDate($start_month, $v);
            $minBirthday[] = $this->jwStudentReg->getBirthDate($end_month, $v);
        }
        $maxBirthday = max($maxBirthday);
        $minBirthday = min($minBirthday);
        // 根据课阶的月龄计算适合上课的宝宝生日范围
        $where['_string'] = "if(mapcase.schage='0000-00-00',(babyinfo.birthday<='{$maxBirthday}' and babyinfo.birthday>'{$minBirthday}'), (mapcase.schage<='{$maxBirthday}' and mapcase.schage>'{$minBirthday}'))";
        list($babyinfo, $total, $total_page) = $this->businessBabyinfo->getBabyinfoByWhere($where, $join);
        foreach ($babyinfo as $key => $val) {
            // 前端复选框用
            $babyinfo[$key]['row_selection'] = ['status' => false, 'msg' => '', 'disabled' => false];
            // 查询共享合同
            $cont_num_exp = explode(',', $val['all_cont_num']);
            $cont_num_arr = $temp_cont_num = $cont_num_exp;
            // 把自己的合同和共享他人的合同合并
            if ($val['cont_num']) {
                $cont_num_arr = array_merge($cont_num_exp, [$val['cont_num']]);
            }
            $condition = [
                'delete'   => 0,
                'org_id'   => $this->org_id,
                'status'   => ['in', [1, 2]],
                'cont_num' => ['in', $cont_num_arr]
            ];
            $contract = $this->jwContract->where($condition)
                ->field('id, cont_num, bid, title, yxq_type, approval, start_date, end_date, status, freeze_start_date, freeze_end_date, package_type, package_day, date_long, dayfree, months, frequency_type, frequency, hours_zheng, hours_zeng, hours_bu, cr_id')
                ->select();
            foreach ($contract as $k => $v) {
                ///
                /// 默认合同优先级
                /// 权重1：包含该课程的>不包含的
                /// 权重2：已开卡>未开卡
                /// 权重3：时段卡>课时卡
                /// 权重4：先到期>后到期
                /// 权重5：未到期>已到期
                /// 权重6：未退费>已退费
                ///
                // 包含该课程的 > 不包含的
                $contract_crids_exp = explode(',', $v['cr_id']);
                $weight1 = (in_array('-1', $contract_crids_exp) || in_array($class['cr_id'], $contract_crids_exp)) ? 1 : 0;
                // 已开卡>未开卡
                $weight2 = ($v['start_date'] != '0000-00-00') ? 2 : 0;
                // 时段卡>课时卡
                $weight3 = ($v['package_type'] == 2) ? 3 : 0;
                // 先到期>后到期
                $weight4 = ($v['end_date'] != '0000-00-00') ? str_replace("-", "", $v['end_date']) : '00000000';
                // 未到期>已到期
                $weight5 = ($v['end_date'] > $today) ? 5 : 0;
                // 未退费>已退费
                $weight6 = ($v['status'] == 2) ? 0 : 6;
                $contract[$k]['sort'] = $weight1 . $weight2 . $weight3 . $weight4 . $weight5 . $weight6;
                ///
                /// 课包合同状态 5种
                /// 锁定/已过期/停卡中/使用中/已退费
                ///
                if ($v['status'] == 1) {
                    // 使用中
                    $status = JwContractEnum::APPROVAL_USED;
                    // 审批状态大于0是锁定
                    if ($v['approval'] > 0) {
                        // 锁定
                        $status = JwContractEnum::APPROVAL_LOCK;
                    } elseif ($v['end_date'] != '0000-00-00' && $v['end_date'] <= $today) {
                        // 已过期
                        $status = JwContractEnum::APPROVAL_EXPIRED;
                    } elseif ($v['is_ready_freeze'] == 1) {
                        // 停卡中
                        $status = JwContractEnum::APPROVAL_STOPCARD;
                    }
                } elseif ($v['status'] == 2) {
                    // 作废 已退费
                    $status = JwContractEnum::APPROVAL_REFUND;
                }
                $contract[$k]['status_msg'] = $status;
                ///
                /// 选择课包
                /// 1.共享合同说明
                /// 2.还未开卡的间接合同
                ///
                // 共享合同说明 如果合同号在$temp_cont_num数组中不存在，说明是共享合同
                if (!in_array($v['cont_num'], $temp_cont_num)) {
                    // 合同所属人
                    $studetname = $this->businessMapcase->where([
                        'bid' => $v['bid']
                    ])->getField('studentname');
                    $share_tips = [
                        '合同名称：' . $v['title'],
                        '合同号：' . $v['cont_num'],
                        '合同所属人：' . $studetname
                    ];
                    $contract[$k]['share_tips'] = $share_tips;
                    $contract[$k]['bid'] = $val['bid'];
                }
                // 还未开卡的间接合同
                if ($v['yxq_type'] == 2 && $v['start_date'] == '0000-00-00') {
                    $indirect_tips = [
                        '约课成功后合同会自动开卡，开卡日期为首次预约的开课日期'
                    ];
                    $contract[$k]['indirect_tips'] = $indirect_tips;
                }
                ///
                /// 课包合同简介
                ///
                if ($v['package_type'] > 0) {
                    $frequency_str = '不限';
                    if ($v['frequency_type'] == 2) {
                        $frequency_str = $v['frequency'] . '次/周';
                    } elseif ($v['frequency_type'] == 3) {
                        $frequency = explode('|', $v['frequency']);
                        $frequency_str = '<div>' . $frequency[0] . '次/周中</div>' . $frequency[1] . '次/周末';
                    }
                    $package_tips = [
                        '合同号：' . $v['cont_num'],
                        '类型：' . JwContractEnum::PACKAGE_TYPE[$v['package_type']],
                        '有效期：' . $v['start_date'] . '至' . $v['end_date'],
                        '上课频次：' . $frequency_str
                    ];
                    if ($v['package_type'] == 1) {
                        $package_tips += [
                            '剩余正课：' . $v['hours_zheng'],
                            '剩余赠课：' . $v['hours_zeng'],
                            '剩余补课：' . $v['hours_bu']
                        ];
                    }
                    $contract[$k]['package_tips'] = $package_tips;
                }
                // 合同有效期 仅针对未开卡的间接合同，如果间接合同已开卡，还是显示合同的有效期日期范围
                if ($v['yxq_type'] == 1) {
                    // 直接合同
                    $contract_validity = $v['start_date'] . '至' . $v['end_date'];
                } else {
                    // 间接合同
                    if ($v['package_type'] == 1) {
                        if ($v['start_date'] != '0000-00-00') {
                            $contract_validity = $v['start_date'] . '至' . $v['end_date'];
                        } else {
                            $contract_validity = "间接(首次约课 + {$v['date_long']}天)";
                        }
                    } else {
                        if ($v['start_date'] != '0000-00-00') {
                            $contract_validity = $v['start_date'] . '至' . $v['end_date'];
                        } else {
                            $endDate = '';
                            if ($v['months'] != 0) {
                                $endDate .= $v['months'] . '月';
                            }
                            $package_day_sum = intval($v['package_day'] + $v['dayfree']);
                            if ($package_day_sum != 0) {
                                $endDate .= $package_day_sum . '天';
                            }
                            $contract_validity = "间接(首次约课 + {$endDate})";
                        }
                    }
                }
                // 剩余天数
                $shengyu = $this->jwContract->getContractShengyuTime($v);
                $contract[$k]['yxq_type'] = $v['yxq_type'];
                $contract[$k]['shengyu_validity'] = $shengyu['shengyu_time'];
                $contract[$k]['contract_validity'] = $contract_validity;
                $contract[$k]['package_type'] = $v['package_type'];
                $contract[$k]['id'] = $v['id'];
                $contract[$k]['title'] = $v['title'];
                // 抵扣課時
                $contract[$k]['dihou_hours'] = $class['hour'];
                $contract[$k]['cont_num'] = $v['cont_num'];
                $contract[$k]['checked'] = false;
                // 剩余课时
                $contract[$k]['shengyu_hours'] = $v['hours_zheng'] + $v['hours_zeng'];
                // 合同类型 课时卡 时段卡
                $contract[$k]['package_type'] = JwContractEnum::PACKAGE_TYPE[$v['package_type']];
                // 查询学员学龄
                if ($val['schage'] != '0000-00-00') {
                    $student_age = $val['schage'];
                } else {
                    $student_age = $val['birthday'];
                }
                $babyinfo[$key]['student_age'] = $this->member->getAge($student_age);
                $babyinfo[$key]['pop_status'] = false;
            }
            // 根据权重排序 从小到大，权重越大(sort)值越小 todo SORT_DESC SORT_ASC
            $sorts = array_column($contract, 'sort');
            array_multisort($sorts, SORT_ASC, $contract);
            $babyinfo[$key]['contract'] = $contract;
            if ($contract) {
                // 取合同第一条作为展示
                $babyinfo[$key]['first_contract'] = $contract[0];
            }
        }
        $data = [
            'total'      => (int)$total,
            'total_page' => $total_page,
            'babyinfo'   => $babyinfo
        ];
        $this->response->show(200, 'success', $data);
    }

    /**
     * 报班约课
     * 顾问筛选列表
     * 获取顾问信息
     */
    public function consultant()
    {
        $data = [];
        $consultant = $this->member->getMember($this->org_id, 'Gw');
        foreach ($consultant as $key => $val) {
            $data[$key]['sale_id'] = $val['uid'];
            $data[$key]['nickname'] = $val['nickname'];
        }
        // 获取已签约过的顾问ID和系统的顾问取差集
        $saleids = $this->businessMapcase->where([
            'status'   => 1,
            'identity' => 'SS',
            'orgid'    => $this->org_id
        ])->getField('sale_id');
        $saleids_new = array_column($consultant, 'uid');
        $diffuids = array_diff($saleids, $saleids_new);
        $sale_list = [];
        if ($diffuids) {
            $sale_list = $this->member->where([
                'uid' => ['in', $diffuids]
            ])->field('uid as sale_id, nickname')->select();
        }
        $this->response->show(200, 'success', array_merge($data, $sale_list));
    }

    /**
     * 所有学员退课列表
     * 已结课的不显示
     * 根据aboutclass_status来显示退课按钮
     * @param int $classid
     */
    public function drop_classlist()
    {
        $classid = I('request.classid', 0, 'intval');
        $class = $this->jwClass->find($classid);
        if (empty($classid) || empty($class)) {
            $this->response->show(400, '班级不存在');
        }
        // 学员信息，家长信息，报课节数，报课状态，操作
        $where['rule.classid'] = $class['id'];
        $where['student.type'] = ['NOT IN', ['QYY', 'ST']];
        $where['student.status'] = 1;
        list($student, $total, $total_page) = $this->jwStudentReg->getStudentByClassid($where);
        $today = date('Y-m-d');
        $student_info = [];
        foreach ($student as $key => $val) {
            // 报课状态
            // 开课中：当前日期 >= 预约的第一节课上课日期
            // 当前日期 <=预约的最后一节课上课日期

            // 未开课：当前日期 <预约的第一节课上课日期
            // 已结课：当前日期>预约的最后一节课的日期，已结课的没有退课按钮
            // 已全部退班的：(删除这个状态，不在列表展示了) 学员的约课已经全都取消了
            $aboutclass_status = 0;
            if ($today >= $val['min_ptdate'] && $today <= $val['max_ptdate']) {
                $aboutclass_msg = '开课中';
            }
            if ($today < $val['min_ptdate']) {
                $aboutclass_msg = '未开课';
            }
            if ($today > $val['max_ptdate']) {
                $aboutclass_msg = '已结课';
                $aboutclass_status = 1;
            }
            $student_info[$key]['bid'] = $val['share_bid'];
            $student_info[$key]['student_id'] = $val['id'];
            $student_info[$key]['classid'] = $val['classid'];
            $student_info[$key]['aboutclass_msg'] = $aboutclass_msg;
            $student_info[$key]['aboutclass_num'] = $val['aboutclass_num'];
            $student_info[$key]['aboutclass_status'] = $aboutclass_status;
            $student_info[$key]['studentname'] = $val['studentname'];
            $student_info[$key]['babysex'] = BusinessBabyEnum::SEX[$val['babysex']];
            $student_info[$key]['parent_username'] = $val['username'];
            $student_info[$key]['parent_relation'] = $val['relation'];
            $student_info[$key]['parent_phone'] = $val['phone'];
        }
        $data = [
            'list'       => $student_info,
            'total'      => $total,
            'total_page' => $total_page
        ];
        $this->response->show(200, 'success', $data);
    }
<<<<<<< HEAD

    /**
     * 某个学员退课列表
     * 出勤=QD&&sign_type=1&&reg_type=0
     * 旷课=QD&&sign_type=3&&reg_type=0
     * 请假=QJ&&reg_type=0
     * 未考勤=YY&&reg_type=0
     * 补课=QD&&reg_type=1
     * @param int $classid 班级ID
     * @param int $baby_id 学员ID
     */
    public function drop_class()
    {
        $classid = I('request.classid', 0, 'intval');
        $baby_id = I('request.baby_id', 0, 'intval');
        if (empty($classid) || empty($baby_id)) {
            $this->response->show(400, '参数错误');
        }
        $class = $this->jwClass->find($classid);
        $stage = $this->jwCourseStage->where(['id' => $class['cs_id']])->getField('title');
        $babyinfo = $this->businessBabyinfo
            ->alias('babyinfo')
            ->join('wzj_home_business_mapcase mapcase ON babyinfo.bid = mapcase.bid', 'left')
            ->field('babyinfo.babyname, babyinfo.babysex, babyinfo.birthday, mapcase.schage, mapcase.studentname')
            ->where(['babyinfo.bid' => $baby_id, 'mapcase.orgid' => $this->org_id])
            ->find();
        // 组装班级及某学员信息
        $classinfo = [
            'title'        => $stage,
            'student_name' => $babyinfo['studentname'] . '(' . $babyinfo['babyname'] . ')/' . BusinessBabyEnum::SEX[$babyinfo['babysex']],
            'school_age'   => $this->member->getAge($babyinfo['birthday']),
            'age'          => $this->member->getAge($babyinfo['schage'] != '0000-00-00' ? $babyinfo['schage'] : $babyinfo['birthday']),
            'birthday'     => str_replace('-', '/', $babyinfo['birthday'])
        ];
        // 查询学员的约课状态
        $where = [
            'rule.classid'      => $classid,
            'student.status'    => 1,
            'student.type'      => ['NOT IN', ['QYY', 'ST']],
            'student.share_bid' => $baby_id,
            'student.org_id'    => $this->org_id,
        ];
        // 前端根据字段显示课时卡时段卡显示合同信息
        $student_class = $this->jwStudentReg
            ->alias('student')
            ->join('wzj_home_jw_class_rule rule ON student.ru_id = rule.id', 'left')
            ->join('wzj_home_jw_contract contract ON student.cont_num = contract.cont_num AND student.bid = contract.bid', 'left')
            ->field('rule.id, rule.ptdate, rule.start_time, rule.end_time, rule.week, rule.tc_id, rule.zj_id, rule.ro_id, contract.package_type, contract.cont_num, contract.title, student.hour, student.hour_bu, student.hour_zeng, student.id as student_id, student.sign_type, student.reg_type, student.type')
            ->where($where)
            ->order('rule.ptdate asc')
            ->select();
        $member = $this->member->getNicknameByOrgid($this->org_id);
        $room = $this->jwClassRoom->getNameByOrgid($this->org_id);
        // 是否禁用 0否1是 已考勤为1 未考勤为0
        $is_disabled = 0;
        foreach ($student_class as $key => $val) {
            if ($val['type'] == 'QD') {
                if ($val['reg_type'] == 0) {
                    if ($val['sign_type'] == 1) {
                        $attendance_status = '出勤';
                    }
                    if ($val['sign_type'] == 3) {
                        $attendance_status = '旷课';
                        $is_disabled = 1;
                    }
                }
                if ($val['reg_type'] == 1) {
                    $attendance_status = '补课';
                }
            }
            if ($val['type'] == 'YY') {
                if ($val['reg_type'] == 0) {
                    // 未考勤可选
                    $is_disabled = 0;
                    $attendance_status = '未考勤';
                    // 如果已过期的置为不可选
                    if (date('Y-m-d') > $val['ptdate']) {
                        $is_disabled = 1;
                    }
                }
            }
            if ($val['type'] == 'QJ' && $val['reg_type'] == 0) {
                $attendance_status = '请假';
            }
            // 如果已过期的置为不可选
            if ($val['ptdate'] < date('Y-m-d')) {
                $is_disabled = 1;
            }
            $student_class[$key]['is_disabled'] = $is_disabled;
            $student_class[$key]['room_name'] = $room[$val['ro_id']];
            $student_class[$key]['zj_name'] = $member[$val['zj_id']];
            $student_class[$key]['tc_name'] = $member[$val['tc_id']];
            $student_class[$key]['week'] = JwClassEnum::WEEK[$val['week']];
            $student_class[$key]['attendance_status'] = $attendance_status;
            unset($student_class[$key]['tc_id']);
            unset($student_class[$key]['zj_id']);
            unset($student_class[$key]['ro_id']);
            unset($student_class[$key]['sign_type']);
            unset($student_class[$key]['reg_type']);
            unset($student_class[$key]['type']);
        }
        $data = [
            'classinfo' => $classinfo,
            'classrule' => $student_class
        ];
        $this->response->show(200, 'success', $data);
    }

    /**
     * 某个学员进行退课/请假
     * @param string $student_ids 报课ID 多个逗号分隔
     * @param int $notice_type 提醒 0否 1短信 2微信 3都有
     */
    public function drop()
    {
        $student_ids = I('request.student_ids', '', 'trim');
        $type = I('request.type', 1, 'intval');
        $notice_type = I('request.notice_type', 0);
        $student_info = $this->jwStudentReg->where([
            'id' => ['in', $student_ids]
        ])->select();
        if (empty($student_ids) || empty($student_info)) {
            $this->response->show(400, '参数错误');
        }
        $data['credit'] = 0;
        $data['uid'] = session('user_auth')['uid'];
        $data['org_id'] = $this->org_id;
        //判断是直接取消 还是 请假
        if ($type == 2) {
            $data['remark'] = '请假';
            $data['type_status'] = 'QJ';
        } else {
            $data['remark'] = '退课';
            $data['type_status'] = 'QYY';
        }
        $data['tips'] = filterEmoji(I('request.qingjia_tips', ''));  //请假原因
        $data['message'] = '';
        // 进行退班操作，把选中的排课进行取消约课操作，并返还课时
        // 已考勤和历史的请假的不支持退课
        foreach ($student_info as $key => $val) {
            $rs = $this->jwStudentReg->putRegStatusNew($val['id'], $data, $val);
            if ($rs['status'] == 1) {
                // TODO 短信

                //宝宝信息
                $baby_data = $this->jwStudentReg->getClassStudentOne($this->org_id, $val['ru_id'], $val['share_bid']);
                //扣课时信息
                $sign_data = M('jw_sign')->where("id =" . $rs['sid'])->find();
                $log_data = array_merge($baby_data, $val, $sign_data);
                $log_data['id'] = $val['id'];
                $log_data['type'] = $data['type_status'];
                $log_data['sms_id'] = 0;
                $log_data['ru_id'] = $val['ru_id'];
                $log_data['send_weixin'] = 0;
                $log_data['sign_hours'] = $val['hour']+$val['hour_zeng'];
                if ($data['type_status'] == 'QJ') {
                    $log_data['qingjia_content'] = $data['tips'];
                }
                D('JwLog')->addLog($this->org_id, $log_data, '删除', 'JWXYQX');
            } else {
                $this->response->show(400, $rs['msg']);
            }
        }
        $this->response->show(200, $data['remark'].'成功');
    }

    /**
     * 排课记录-右侧滑块
     * @param int $rule_id
     */
    public function class_detail()
    {
        $rule_id = I("request.rule_id", 0, 'intval');
        if (empty($rule_id)) {
            $this->response->show(400, '参数错误');
        }
        // 获取课程配置详情
        $class_rule = $this->jwClassRule->alias('rule')
            ->join('wzj_home_jw_class class ON rule.classid = class.id')
            ->field('rule.*, class.hour')
            ->where(['rule.org_id' => $this->org_id, 'rule.id' => $rule_id, 'rule.status' => 1])
            ->find();
        if (empty($class_rule)) {
            $this->response->show(400, '没有此课程数据');
        }
        //所有老师
        $member = $this->member->getNicknameByOrgid($this->org_id);
        // 课阶详情
        $course_stage = $this->jwCourseStage->getCourseCsidStage($class_rule['cs_id']);
        // 所属分类名称
        $course_cate_name = M('brand_course')->where("id = {$course_stage['cr_id']}")->getField('title');
        //所有教室
        $class_rooms = $this->jwClassRoom->where("org_id={$this->org_id}")->getField('id,name');
        //课阶的年龄范围
        $rule = [];
        $rule['course_month'] = monthToAge($course_stage['start_mouth']) . ' - ' . monthToAge($course_stage['end_mouth']);
        $rule['id'] = $class_rule['id'];
        $rule['max_stu'] = $class_rule['max_stu'];
        $rule['tc_name'] = $member[$class_rule['tc_id']];
        $rule['zj_name'] = $member[$class_rule['zj_id']];
        $rule['cate_name'] = $course_cate_name;
        $rule['title'] = $course_stage['title'];
        $rule['credit'] = $course_stage['credit'];
        $rule['truancy_credit'] = $course_stage['truancy_credit'];
        $rule['ptdate'] = $class_rule['ptdate'];
        $rule['start_time'] = $class_rule['start_time'];
        $rule['end_time'] = $class_rule['end_time'];
        $rule['hour'] = $class_rule['hour'];
        $rule['class_room'] = $class_rooms[$class_rule['ro_id']];
        //已预约 未预约 试听位学员数量
        $stus_num = $this->jwStudentReg->getRuleStusNew($this->org_id, $rule_id);
        $rule['formal_num'] = count($stus_num['formal']);
        $rule['audition_num'] = count($stus_num['audition']);
        $rule['data'] = $stus_num;
        $this->response->show(200, 'success', $rule);
    }

    /**
     * TODO 短信模板还未填充
     * 获取签到学员的状态
     * @param int $student_id
     * @param string $type
     */
    public function getstu_status()
    {
        $reg_id = I('request.student_id', 0, 'intval');
        // 操作类型
        // 考勤type: QD
        // 请假type: QJ
        // 取消type: QYY
        $type = I('request.type');
        $rs = $this->jwStudentReg->getStuStatusNew($reg_id, $type);
        //获取微信模板编号
        switch ($type) {
            case 'QD':
                $number_id = 'OPENTM406123046';
                break;
            case 'QJ':
                $number_id = 'OPENTM411056091';
                break;
            case 'QYY':
                $number_id = 'OPENTM410800250';
                break;
            default:
                $number_id = '';
        }
        $JwTempStatus = D('JwTempRemind')->getTempOpen($number_id, $rs['openid']);
        $this->response->show(200, 'success', array_merge($rs, $JwTempStatus));
    }

    /**
     * 单个学员考勤
     * @param string $student_ids 多个逗号分隔
     * @param int $sign_type 出勤类型 默认值1 1出勤2旷课
     * @param int $can_buke 是否可补课 0否1是
     */
    public function sign()
    {
        $student_ids = I('request.student_ids', '', 'trim');
        $notice_type = I('request.notice_type', 0);
        $data['org_id'] = $this->org_id;
        $data['app_id'] = $this->app_id;
        $data['uid'] = session('user_auth')['uid'];
        $data['type'] = 'DQD';
        $data['type_status'] = 'QD';
        $data['sign_type'] = I('request.sign_type', 1, 'intval');
        $data['can_buke'] = I('request.can_buke', 0, 'intval');
        $sign_types = array(
            1 => '已到',
            3 => '旷课'
        );
        $data['remark'] = isset($sign_types[$data['sign_type']]) ? $sign_types[$data['sign_type']] : '已到';
        $data['message'] = '';
        $data['ctime'] = date('Y-m-d H:i:s');
        if (strpos($student_ids, ',')) {
            $student_ids = explode(',', $student_ids);
        } else {
            $student_ids = [$student_ids];
        }
        if (empty($student_ids)) {
            $this->response->show(400, '参数错误');
        }
        foreach ($student_ids as $key => $student_id) {
            $rs = $this->jwStudentReg->putRegStatusNew($student_id, $data);
            if ($rs['status'] == 1) {
                // TODO 短信

                $student = $this->jwStudentReg->where([
                    'id' => $student_id
                ])->find();
                $sign_data = $rs['sign_data'];
                $baby_data = $this->jwStudentReg->getClassStudentOne($this->org_id, $sign_data['ru_id'], $sign_data['share_bid']);
                $log_data = array_merge($sign_data, $baby_data);
                $log_data['id'] = $rs['sid'];
                $log_data['ru_id'] = $student['ru_id'];
                $log_data['sms_id'] = 0;
                $log_data['send_weixin'] = '';
                $log_data['type'] = 'YY';
                D('JwLog')->addLog($this->org_id, $log_data, '添加', 'JWXYQD');
            } else {
                $this->response->show(400, $rs['msg']);
            }
        }
        $this->response->show(200, '考勤成功');
    }

    /**
     * 撤销签到
     * @param int $student_id
     */
    public function revoke_sign()
    {
        $student_id = I('request.student_id', 0, 'intval');
        $notice_type = I('request.notice_type', 0);
        //预约记录
        $student_info = $this->jwStudentReg->where([
            'id'     => $student_id,
            'org_id' => $this->org_id
        ])->find();
        if (empty($student_info)) {
            $this->response->show(400, '没有找到预约记录');
        }
        if ($student_info['leave_school'] == 1) {
            $this->response->show(401, '已离校学员不支持撤销考勤', ['ru_id' => $student_info['ru_id']]);
        }
        $comment = M('jw_comment')->where("reg_id='{$student_id}' and ru_id='{$student_info['ru_id']}' and share_bid='{$student_info['share_bid']}'")->getField('id');
        if ($comment) {
            $this->response->show(400, '家长回评后不能撤销考勤');
        }
        $eval = M('jw_eval')->where("reg_id='{$student_id}' and share_bid='{$student_info['share_bid']}'")->getField('id');
        if ($eval) {
            $this->response->show(400, '课评后不能撤销考勤');
        }
        //签到信息，用于记录log
        $sign_data = M('jw_sign')->where("share_bid={$student_info['share_bid']} and ru_id={$student_info['ru_id']} and org_id={$this->org_id}")->order('id desc')->find();
        //学员信息
        $baby_data = $this->jwStudentReg->getClassStudentOne($this->org_id, $student_info['ru_id'], $student_info['share_bid']);  //获取baby的部分信息
        $log_data = array_merge($baby_data, $sign_data);
        //撤销签到
        $data = array(
            'org_id'      => $this->org_id,
            'type_status' => 'QQD',
            'm_pid'       => 0,
            'uid'         => session('user_auth')['uid'],
            'remark'      => '',
            'tips'        => ''
        );
        $data['message'] = '';
        $rs = $this->jwStudentReg->putRegStatusNew($student_id, $data);
        if ($rs['status'] == 1) {
            // TODO 短信

            $log_data['sms_id'] = 0;
            $log_data['sign_action'] = 12;
            if($data['type_status'] == 'QQD' && $student_info['sign_type'] == 3 && $student_info['type'] == 'QD'){
                $log_data['sign_hours'] = -1 * ($student_info['hour'] + $student_info['hour_zeng']);
            }
            D('JwLog')->addLog($this->org_id, $log_data, '删除', 'JWCXQD');
            $this->response->show(200, '撤销考勤成功');
        } else {
            $this->response->show(400, $rs['msg']);
        }
    }

    /**
     * 批量考勤
     * 显示未考勤学员
     * @param int $rule_id
     */
    public function no_attend_students()
    {
        $rule_id = I('request.rule_id', 0, 'intval');
        $list = $this->jwStudentReg->alias('student')
            ->field('student.id as student_id, mapcase.studentname')
            ->join('wzj_home_business_mapcase mapcase ON student.org_id = mapcase.orgid AND mapcase.bid = student.share_bid')
            ->where([
                'student.type'   => ['in', ['YY', 'QQD']],
                'student.status' => 1,
                'student.ru_id'  => $rule_id,
                'org_id'         => $this->org_id
            ])->order('student.id asc')->select();
        $this->response->show(200, '获取成功', $list);
    }

    /**
     * 取消补课
     * @param int $student_id 学员id
     * @param int $notice_type 通知方式1：短信 2：微信
     */
    public function cancel_buke()
    {
        $student_id = I('post.student_id', 0, 'intval');
        $notice_type = I('request.notice_type', 0);
        $content = I('request.content', '');
        if (empty($student_id)) {
            $this->response->show(400, '参数错误');
        }
        $studentInfo = M('JwStudentReg')->where(['id' => $student_id, 'type' => 'QD', 'reg_type' => 1])->find();

        if (empty($studentInfo)) {
            $this->response->show('400', '没有找到补课记录');
        }
        $eval = M('jw_eval')->where("reg_id='{$student_id}' and share_bid='{$studentInfo['share_bid']}'")->getField('id');
        if ($eval) {
            $this->response->show(400, '课评后不能取消补课');
        }
        $org_id = $this->org_id;

        $hour = $studentInfo['hour_bu'];
        $cont_num = $studentInfo['cont_num'];
        $bid = $studentInfo['bid'];
        $share_bid = $studentInfo['share_bid'];
        $ru_id = $studentInfo['ru_id'];
        $redis = getRedis();

        $countKey = D('JwClassRule')->getRuleBabysCountKey($org_id,$ru_id);
        $countKeyExists = $redis->exists($countKey);
        if($countKeyExists){
            $redis->decr($countKey);
        }

        if ($studentInfo) {
            M()->startTrans();
            $data['type'] = 'QYY';
            $data['reg_type'] = 0;
            $data['utime'] = date('Y-m-d H:i:s', time());
            $data['hour_bu'] = 0;
            $res = M('JwStudentReg')->where("id={$student_id}")->save($data);

            if ($res) {
                //扣除课时
                //查看是否是共享合同
                $sharInfo = M('JwContractShare')->where(['share_bid' => $bid, 'cont_num' => $cont_num, 'status' => 1])->find();
                if ($sharInfo && $sharInfo['bid']) {
                    $bid = $sharInfo['bid'];
                }
                $contractInfo = M('JwContract')
                    ->where([
                        'bid'      => $bid,
                        'cont_num' => $cont_num,
                        'org_id'   => $this->org_id])
                    ->setInc('hours_bu', $hour);

                //同步到sign表中
                $student_info = M('JwStudentReg')->where("id = {$student_id}")->find();
                //同步到sign表中
                $sign_data = $student_info;
                $sign_data['hour_bu'] = $hour;
                $sign_data['cont_num'] = $cont_num;
                $sign_data['buke'] = 0;
                $sign_data['type'] = 'ATJ';
                $sign_data['type_status'] = 'QYY';
                $sign_data['tips'] = $content;
                unset($sign_data['id']);

                $sign_id = M('JwSign')->add($sign_data);

                //同步到log表中
                $sign_data = M('jw_sign')->where("id = {$sign_id}")->find();
                $ru_id = $sign_data['ru_id'];
                $share_bid = $sign_data['share_bid'];
                $org_id = $this->org_id;
                $baby_data = $this->jwStudentReg->getClassStudentOne($org_id, $ru_id, $share_bid);
                $log_data = array_merge($baby_data, $sign_data);
                $log_data['sms_id'] = 0;
                $log_id =  D('JwLog')->addLog($this->org_id, $log_data, '取消', 'JWXYQB');
                if ($contractInfo && $sign_id && $log_id){
                    M()->commit();
                    $ruleBabysSetKey = D('JwClassRule')->getRuleBabysSetKey($org_id,$ru_id);
                    $redis->sRem($ruleBabysSetKey,$share_bid);
                    $this->response->show('200', '取消成功');
                } else {
                    M()->rollback();
                    $this->response->show('400', '取消失败');
                }
            }
        }
    }

    /**
     * 添加补课页面数据
     * $ru_id 课程的id
     */
    public function create_buke_data()
    {
        $rule_id = I('post.rule_id', 0, 'intval');
        if (empty($rule_id)) {
            $this->response->show(400, '参数异常');
        }

        $name = I('request.name', '');

        if ($name) {
            $swhere['mapcase.studentname'] = ['like', '%' . $name . '%'];
            $swhere['babyinfo.babyname'] = ['like', '%' . $name . '%'];
            $swhere['parentinfo.phone'] = ['like', '%' . $name . '%'];
            $swhere['_logic'] = 'or';
            $where['_complex'] = $swhere;
        }


        $org_id = $this->org_id;

        $rule_where['rule.id'] = $rule_id;
        $rule_where['rule.org_id'] = $org_id;
        $RuleInfo = M('JwClassRule')->alias('rule')
            ->join('wzj_home_jw_course_stage stage ON rule.cs_id = stage.id')
            ->join('wzj_home_jw_class_room room ON rule.ro_id = room.id')
            ->join('wzj_home_member tc ON rule.tc_id = tc.uid')
            ->join('wzj_home_jw_class class ON rule.classid = class.id')
            ->join('wzj_home_member zj ON rule.zj_id = zj.uid')
            ->field('rule.id,stage.title,rule.ptdate,rule.strtime,class.hour,room.name,tc.nickname as tc_name,zj.nickname as zj_name,stage.cr_id')
            ->where($rule_where)
            ->find();
        $cr_id = $RuleInfo['cr_id'] ? $RuleInfo['cr_id'] : 0;

        // 取出签约共享人员
        $qianyue = M('business_mapcase')->where([
            'identity' => 'SS',
            'status'   => 1,
            'org_id'   => $this->org_id
        ])->select();

        $qianyueids = array_column($qianyue, 'bid');

        if (!empty($qianyueids)) {
            $share = M('jw_contract_share')->where([
                'bid'    => ['in', $qianyueids],
                'org_id' => $this->org_id
            ])->getField('share_bid', true);
            $xueyuanids = array_merge($qianyueids, $share);
            $where['mapcase.bid'] = ['in', $xueyuanids];
        }

        $join = "AND (find_in_set({$cr_id}, contract.cr_id) or contract.cr_id = '-1')";
        $babyInfo = $this->businessBabyinfo->getBabyContractInfo($where, $join);

        foreach ($babyInfo as $k => $val) {
            $age = getAge($val['birthday']);
            $babyInfo[$k]['age'] = $age;
            $condition = "`delete` = 0 and org_id = {$this->org_id}  and status in (1,2) and (bid = {$val['bid']} or cont_num = '{$val['cont_num']}')";
            $contract = $this->jwContract
                ->where($condition)
                ->field('id, cont_num, title, is_share ,hours_bu ,cr_id,bid')
                ->select();
            foreach ($contract as $ka => $v) {
                $contract[$ka]['share'] = '非共享';
                if ($v['bid'] != $val['bid']) {
                    $contract[$ka]['bid'] = $v['bid'];
                    $contract[$ka]['share'] = '共享';
                }
            }
            $babyInfo[$k]['contract'] = $contract;
        }

        $return_data = array(
            'ruleinfo'     => $RuleInfo,
            'contractinfo' => $babyInfo
        );
        $this->response->show(200, 'success', $return_data);
    }

    /**
     * 添加补课
     * int ruid 排课id
     * int bid  当前学员bid
     * int use_bid 当前学员使用共享合同bid （所属人）
     * int pid  家长id
     * int saleid  顾问id
     * string contnum 合同号
     * string hout 消耗课时
     * int noticetype  提醒 0否 1短信 2微信 3都有
     * string content 备注信息
     */
    public function create_buke()
    {
        $ru_id = I('post.ruid', 0, 'intval');
        $bid = I('post.bid', 0, 'intval');
        $pid = I('post.pid', 0, 'intval');
        $use_bid = I('post.useid', 0, 'intval');
        $sale_id = I('post.saleid', 0, 'intval');
        $cont_num = I('post.contnum', '');
        $bkhour = I('post.hour', '');
        $notice_type = I('post.noticetype', '');
        $content = I('post.content', '');
        $is_ok = I('post.is_ok', '');
        if (empty($ru_id) || empty($bid) || empty($cont_num)  || empty($use_bid)) {
            $this->response->show(400, '参数异常');
        }
        if (empty($bkhour)){
            $this->response->show(400,'补课课时数不能为0');
        }
        $org_id = $this->org_id;
        $uid = $this->uid;

        //判断是否符合补课条件
        $buInfo = M('JwStudentReg')->where([
            'org_id'    => $org_id,
            'ru_id'     => $ru_id,
            'share_bid' => $bid,
        ])->field('id,type,reg_type,sign_type')->find();

        if ($buInfo['type'] == 'QD' || $buInfo['type'] == 'YY') {
            $this->response->show('400', '该学员已经不可在进行补课');
        }

        if ($is_ok != 1) {
            //获取班级的容量是否满足补课
            $max_stu = M('JwClassRule')->where("id = {$ru_id}")->field('max_stu')->find();
            $maxwhere['ru_id'] = $ru_id;
            $maxwhere['type'] = ['in',['YY','QJ','QD','QQD']];
            $class_max = M('JwStudentReg')->where($maxwhere)->field('count(distinct(share_bid)) as num')->find();

            if ($max_stu['max_stu'] <= $class_max['num']) {
                $this->response->show(401, '本节课程已满员，是否还要继续约补课');
            }
        }

        if (($bkhour * 10) % 5 != 0) {
            $this->response->show(400, '单节消耗课时请输入0.5的倍数');
        }

        //根据bid和合同号获取当前剩余补课课时
        //判断当前是否使用的是自己的合同


        $hour_bk = M('JwContract')->
        where([
            'bid'      => $use_bid,
            'cont_num' => $cont_num,
            'org_id'   => $org_id
        ])
            ->field('bid,hours_bu')
            ->find();

        if ($bkhour > $hour_bk['hours_bu']) {
            $this->response->show(400, '合同剩余补课课时数不足');
        }

        $redis = getRedis();
        $ruleBabysSetKey = D('JwClassRule')->getRuleBabysSetKey($org_id,$ru_id);
        if(!$redis->sAdd($ruleBabysSetKey,$bid)){
            $this->response->show(400,'学员已约本节课，预约失败了');
        }

        $countKey = D('JwClassRule')->getRuleBabysCountKey($org_id,$ru_id);
        $countKeyExists = $redis->exists($countKey);
        if($countKeyExists){
            $redis->incr($countKey);
        }

        $nowtime = time();
        M()->startTrans();
        if ($buInfo) { //满足补课的学生 进行更新数据
            $student_id = $buInfo['id'];
            $data['bid'] = $use_bid;
            $data['share_bid'] = $bid;
            $data['type'] = 'QD';
            $data['reg_type'] = 1;
            $data['utime'] = date('Y-m-d H:i:s', $nowtime);
            $data['cont_num'] = $cont_num;
            $data['hour_bu'] = $bkhour;
            $create_info = M('JwStudentReg')->where("id={$student_id}")->save($data);
        } else {//添加数据
            $data['org_id'] = $org_id;
            $data['ptdate'] = date('Y-m-d', $nowtime);
            $data['ru_id'] = $ru_id;
            $data['uid'] = $uid;
            $data['pid'] = $pid;
            $data['bid'] = $use_bid;
            $data['share_bid'] = $bid;
            $data['sale_id'] = $sale_id;
            $data['type'] = 'QD';
            $data['reg_type'] = 1;
            $data['status'] = 1;
            $data['ctime'] = date('Y-m-d H:i:s', $nowtime);
            $data['utime'] = date('Y-m-d H:i:s', $nowtime);
            $data['cont_num'] = $cont_num;
            $data['hour_bu'] = $bkhour;
            $create_info = $this->jwStudentReg->add($data);
        }

        if ($create_info) {
            //扣减课时
            $contract_buke = M('JwContract')->where(['bid' => $use_bid, 'cont_num' => $cont_num, 'org_id' => $org_id])->setDec('hours_bu', $bkhour);

            //同步到sign表中
            //判断是否符合补课条件
            $dataInfo = M('JwStudentReg')->where([
                'org_id'    => $org_id,
                'ru_id'     => $ru_id,
                'share_bid' => $bid,
            ])->find();
            unset($dataInfo['id']);
            $sign_data = $dataInfo;
            $sign_data['cont_num'] = $cont_num;
            $sign_data['hour_bu'] = '-' . $bkhour;
            $sign_data['buke'] = '1';
            $sign_data['type'] = 'DJS';
            $sign_data['type_status'] = 'QD';
            $sign_data['tips'] = $content;
            $sign_id = M('JwSign')->add($sign_data);

            //同步到log表中
            $sign_data = M('jw_sign')->where("id = {$sign_id}")->find();

            $baby_data = $this->jwStudentReg->getClassStudentOne($org_id, $ru_id, $bid);

            $log_data = array_merge($baby_data, $sign_data);
            //撤销签到
            $log_data['org_id'] = $org_id;
            $log_data['sms_id'] = 0;
            $log_id = D('JwLog')->addLog($this->org_id, $log_data, '添加', 'JWXYBK');

            if ($contract_buke && $sign_id && $log_id) {
                M()->commit();
                $this->response->show(200, '添加补课成功');
            }else{
                M()->rollback();
                //如果预约失败
                if($countKeyExists){
                    $redis->decr($countKey);
                }
                $redis->sRem($ruleBabysSetKey,$bid);
                $this->response->show(400, '添加失败');
            }
        }else{
            //如果预约失败
            M()->rollback();
            if($countKeyExists){
                $redis->decr($countKey);
            }
            $redis->sRem($ruleBabysSetKey,$bid);
            $this->response->show(400, '添加失败');
        }
    }

    //添加试听听课学员 --页面数据
    public function create_st()
    {
        $rule_id = I('post.rule_id', 0, 'intval');
        if (empty($rule_id)) {
            $this->response->show(400, '参数异常');
        }
        $name = I('request.name', '');

        if ($name) {
            $swhere['xs.studentname'] = ['like', '%' . $name . '%'];
            $swhere['babyinfo.babyname'] = ['like', '%' . $name . '%'];
            $swhere['parentinfo.phone'] = ['like', '%' . $name . '%'];
            $swhere['_logic'] = 'or';
            $where['_complex'] = $swhere;
        }

        $org_id = $this->org_id;
        $rule_where['rule.id'] = $rule_id;
        $rule_where['rule.org_id'] = $org_id;
        $RuleInfo = M('JwClassRule')->alias('rule')
            ->join('wzj_home_jw_course_stage stage ON rule.cs_id = stage.id')
            ->join('wzj_home_jw_class_room room ON rule.ro_id = room.id')
            ->join('wzj_home_member tc ON rule.tc_id = tc.uid')
            ->join('wzj_home_member zj ON rule.zj_id = zj.uid')
            ->field('rule.id,stage.title,rule.ptdate,rule.strtime,room.name,tc.nickname as tc_name,zj.nickname as zj_name,stage.cr_id')
            ->where($rule_where)
            ->find();
        // 取出签约共享人员
        $shitin = M('XsStudents')->where([
            'identity' => 'BB',
            'org_id'   => $this->org_id
        ])->select();

        $where['xs.identity'] = 'BB';
        $where['xs.org_id'] = $this->org_id;

        $babyInfo = $this->businessBabyinfo->getBabyInfo($where);

        foreach ($babyInfo as $k => $v) {
            $age = getAge($v['birthday']);
            $babyInfo[$k]['age'] = $age;
        }

        $return_data = array(
            'rule_info' => $RuleInfo,
            'baby_info' => $babyInfo
        );
        $this->response->show(200, 'success', $return_data);
    }

    /**
     * 约课记录页面
     */
    public function yueke_index()
    {
        $this->display();
    }

    /**
     * 约课记录
     * @param int $babyname 学员名称
     * @param int $username 家长名称
     * @param int $stage_title 课程名称
     * @param int $phone 家长手机
     * @param int $teach_name 授课老师
     * @param int $fuban_name 辅班老师
     * @param int $sales_name 所属顾问
     * @param int $type 类型 正式位 未考勤 出勤 请假 旷课 补课 课评 未评 回评 未回评
     */
    public function yueke_list()
    {
        $type_status = ['正式位' => 0, '未考勤' => 0, '出勤' => 0, '请假' => 0, '旷课' => 0, '补课' => 0, '课评' => 0, '未评' => 0, '回评' => 0, '未回评' => 0];
        // 学员姓名及昵称
        $babyname = I('request.babyname', '', 'trim');
        if (!empty($babyname)) {
            $bid = $this->businessBabyinfo->alias('babyinfo')
                ->join('wzj_home_business_mapcase mapcase ON babyinfo.bid = mapcase.bid')
                ->where(['babyinfo.babyname|mapcase.studentname' => ['like', '%' . $babyname . '%']])
                ->field('babyinfo.bid')
                ->select();
            if (empty($bid)) {
                $bid = null;
            }
            $bid = array_column($bid, 'bid');
            $where['bid'] = $bid;
        }
        // 家长姓名
        $username = I('request.username', '', 'trim');
        if (!empty($username)) {
            $parent_pid = $this->businessParentinfo->where([
                'username' => ['like', '%' . $username . '%']
            ])->getField('pid', true);
            $where['pid'] = $parent_pid;
        }
        // 家长手机
        $phone = I('request.phone', '', 'trim');
        if (!empty($phone)) {
            $parent_pid = $this->businessParentinfo->where([
                'phone' => $phone
            ])->getField('pid', true);
            $where['pid'] = $parent_pid;
        }
        // 课程名称
        $stage_id = I('request.stage_id', '', 'trim');
        if (!empty($stage_id)) {
            $where['cs_id'] = $stage_id;
        }
        // 模糊搜索老师(授课，辅班，顾问)
        $teach_name = I('request.teach_name', '', 'trim');
        $fuban_name = I('request.fuban_name', '', 'trim');
        $sales_name = I('request.sales_name', '', 'trim');
        if (!empty($teach_name) || !empty($fuban_name) || !empty($sales_name)) {
            $uids = $this->member->where([
                'nickname' => ['like', '%' . $teach_name . $fuban_name . $sales_name . '%']
            ])->getField('uid', true);
            if (!empty($teach_name)) {
                $where['tc_id'] = $uids;
            }
            if (!empty($fuban_name)) {
                $where['zj_id'] = $uids;
            }
            if (!empty($sales_name)) {
                $where['sale_id'] = $uids;
            }
        }
        // 日期搜索 格式 2020-01-01至2020-01-02
        $ptdate = I('request.ptdate', '', 'trim');
        $now_date = date('Y-m') . '-01';
        $low_date = date('Y-m-d');
        if (!empty($ptdate)) {
            $ptdate_exp = explode('至', $ptdate);
            $now_date = $ptdate_exp[0];
            $low_date = $ptdate_exp[1];
        }
        $where['ptdate'] = [$now_date, $low_date];
        $type = I('request.type');
        if (!empty($type)) {
            $where['type'] = I('request.type');
        }
        $where['org_id'] = $this->org_id;
        $data = $this->jwStudentReg->yueke($where);
        $yueke = $data['yueke'];
        $total = $data['total'];
        $total_page = $data['total_page'];
        $keping_num = $data['keping_num'];
        $room = $this->jwClassRoom->getNameByOrgid($this->org_id);
        $member = $this->member->getNicknameByOrgid($this->org_id);
        $getsale = $this->member->getmember($this->org_id);
        $yueke_list = [];
        foreach ($yueke as $key => $val) {
            // 考勤查出学分
            $stage = M('jw_course_stage')->where([
                'id' => $val['cs_id'],
                'org_id' => $this->org_id
            ])->find();
            $yueke_list[$key]['credit'] = $stage['credit'];
            $yueke_list[$key]['truancy_credit'] = $stage['truancy_credit'];
            // 课程信息
            $title = $this->jwCourseStage->where([
                'id' => $val['cs_id']
            ])->getField('title');
            $yueke_list[$key]['student_id'] = $val['id'];
            $yueke_list[$key]['type'] = $val['type'];
            $yueke_list[$key]['reg_type'] = $val['reg_type'];
            $yueke_list[$key]['package_type'] = $val['package_type'];
            $yueke_list[$key]['sign_type'] = $val['sign_type'];
            $yueke_list[$key]['ru_id'] = $val['ru_id'];
            $yueke_list[$key]['bid'] = $val['share_bid'];
            // 学员信息
            $yueke_list[$key]['babyname'] = $val['babyname'];
            $yueke_list[$key]['studentname'] = $val['studentname'];
            $yueke_list[$key]['babysex'] = BusinessBabyEnum::SEX[$val['babysex']];
            $yueke_list[$key]['schoolage'] = $this->member->getAge($val['schage'] ? $val['schage'] : $val['birthday']);
            if (empty($val['headimg'])) {
                $val['headimg'] = C('file_url') . BusinessBabyEnum::BABY_AVATAR[$val['babysex']];
            }
            $yueke_list[$key]['babyavatar'] = $val['headimg'];
            // 上课信息
            $yueke_list[$key]['title'] = $title;
            $yueke_list[$key]['ptdate'] = $val['ptdate'];
            $yueke_list[$key]['room_name'] = $room[$val['ro_id']];
            $yueke_list[$key]['start_time'] = $val['start_time'];
            $yueke_list[$key]['end_time'] = $val['end_time'];
            // 家长信息
            $yueke_list[$key]['relation'] = $val['relation'];
            $yueke_list[$key]['username'] = $val['username'];
            $yueke_list[$key]['phone'] = $val['phone'];
            if (empty($val['p_headimg'])) {
                $val['p_headimg'] = BusinessBabyEnum::PARENT_AVATAR[$val['relation']];
            }else{
                $val['p_headimg'] = C('file_url') . $val['p_headimg'];
            }
            $yueke_list[$key]['avatar'] = $val['p_headimg'];
            // 约课操作 所属顾问 主讲 辅班
            $yueke_list[$key]['yueke_name'] = $member[$val['uid']];
            $yueke_list[$key]['sale_name'] = $member[$val['sale_id']];
            $yueke_list[$key]['tc_name'] = $member[$val['tc_id']];
            $yueke_list[$key]['zj_name'] = $member[$val['zj_id']];
            // 当前消耗课时包
            $yueke_list[$key]['contract_title'] = $val['title'];
            $yueke_list[$key]['cont_num'] = $val['cont_num'];
            // 时段卡才有扣减课时的信息 时段卡没有
            $yueke_list[$key]['hour'] = $val['hour'];
            $yueke_list[$key]['hour_zeng'] = $val['hour_zeng'];
            $yueke_list[$key]['hour_bu'] = $val['hour_bu'];
            // 考勤状态
            list($operate_detail, $status_msg) = $this->jwStudentReg->ruleStatus($val);
            $yueke_list[$key]['status_msg'] = $status_msg;
            $yueke_list[$key]['operate_detail'] = $operate_detail;
            // 考勤状态回显
            $sign = D("JwSign")->where([
                'ru_id'       => $val['ru_id'],
                'bid'         => $val['bid'],
                'org_id'      => $this->org_id,
                'type_status' => $val['type']
            ])->field('uid, hour, credit, hour_zeng, hour_bu, type, message, ctime')->find();
            if (!empty($sign)) {
                $sign['nickname'] = $getsale[$sign['uid']]['nickname'];
            }
            $yueke_list[$key]['attendance'] = $sign;
        }
        $where_sql = $this->FunWhere($where);
        $where_type = "and student.type in ('YY','QQD','QD','QJ') and student.status = 1";
        $type_status['正式位'] = $this->jwStudentReg->yueke_count("rule.org_id={$this->org_id} {$where_type} {$where_sql}");
        $type_status['未考勤'] = $this->jwStudentReg->yueke_count("rule.org_id={$this->org_id} and student.type='YY' and student.reg_type = 0 {$where_type} {$where_sql}");
        $type_status['出勤'] = $this->jwStudentReg->yueke_count("rule.org_id={$this->org_id} and student.type='QD' and student.sign_type = 1 and student.reg_type = 0 {$where_type} {$where_sql}");
        $type_status['请假'] = $this->jwStudentReg->yueke_count("rule.org_id={$this->org_id} and student.type='QJ' and student.reg_type = 0 {$where_type} {$where_sql}");
        $type_status['旷课'] = $this->jwStudentReg->yueke_count("rule.org_id={$this->org_id} and student.type='QD' and student.reg_type = 0 and student.sign_type = 3 {$where_type} {$where_sql}");
        $type_status['补课'] = $this->jwStudentReg->yueke_count("rule.org_id={$this->org_id} and student.type='QD' and student.reg_type = 1 {$where_type} {$where_sql}");
        $type_status['课评'] = $this->jwStudentReg->alias('student')
            ->join('wzj_home_jw_class_rule rule ON student.ru_id = rule.id')
            ->join('wzj_home_jw_eval eval ON student.id = eval.reg_id')
            ->join('wzj_home_jw_course_stage stage ON stage.id = rule.cs_id')
            ->where("rule.org_id={$this->org_id} {$where_type} {$where_sql}")
            ->count();
        $type_status['未评'] = $type_status['正式位'] - $keping_num;
        $type_status['回评'] = $this->jwStudentReg->yueke_count("rule.org_id={$this->org_id} and student.iscomment = 1 {$where_type} {$where_sql}");
        $type_status['未回评'] = $this->jwStudentReg->yueke_count("rule.org_id={$this->org_id} and student.iscomment = 0 {$where_type} {$where_sql}");
        $data = [
            'search_default' => [
                'now_date' => $now_date,
                'low_date' => $low_date
            ],
            'type'           => $type_status,
            'list'           => $yueke_list,
            'total'          => $total,
            'total_page'     => $total_page
        ];
        $this->response->show(200, 'success', $data);
    }

    /**
     * 约课记录使用方法
     * @param array $where
     * @return string
     */
    function FunWhere($where = [])
    {
        unset($where['org_id']);
        unset($where['status']);
        if (isset($where['type'])) {
            unset($where['type']);
        }
        $where_str = '';
        if ($where['bid']) {
            $bid_str = implode(',', $where['bid']);
            $where_str .= " and student.share_bid in ({$bid_str})";
            unset($where['bid']);
        }
        if ($where['pid']) {
            $pid_str = implode(',', $where['pid']);
            $where_str .= " and student.pid in ({$pid_str})";
            unset($where['pid']);
        }
        if ($where['sale_id']) {
            $sale_id_str = implode(',', $where['sale_id']);
            $where_str .= " and student.sale_id in ({$sale_id_str})";
            unset($where['sale_id']);
        }
        if ($where['cs_id']) {
            $where_str .= " and rule.cs_id = ({$where['cs_id']})";
            unset($where['cs_id']);
        }
        if ($where['tc_id']) {
            $tc_id_str = implode(',', $where['tc_id']);
            $where_str .= " and rule.tc_id in ({$tc_id_str})";
            unset($where['tc_id']);
        }
        if ($where['zj_id']) {
            $zj_id_str = implode(',', $where['zj_id']);
            $where_str .= " and rule.zj_id in ({$zj_id_str})";
            unset($where['zj_id']);
        }
        if ($where['ptdate']) {
            $where_str .= " and (rule.ptdate >= '" . $where['ptdate'][0] . "' and rule.ptdate <= '" . $where['ptdate'][1] . "')";
            unset($where['ptdate']);
        }
        foreach ($where as $k => $v) {
            if (!empty($v)) {
                $where_str .= " and rule.{$k} like '%{$v}%'";
            }
        }
        return $where_str;
    }

    /**
     * 约课使用方法
     * @param array $item
     * @return string
     * @final
     */
    function status_msg($item = [])
    {
        $status_msg = '';
        if ($item['type'] == 'QD') {
            if ($item['reg_type'] == 0) {
                if ($item['sign_type'] == 1) {
                    $status_msg = '出勤';
                }
                if ($item['sign_type'] == 3) {
                    if ($item['can_buke'] == 0) {
                        $status_msg = '旷课不可补课';
                    } else {
                        $status_msg = '旷课可补课';
                    }
                }
            } elseif ($item['reg_type'] == 1) {
                $status_msg = '补课';
            }
        }
        if ($item['type'] == 'YY') {
            if ($item['reg_type'] == 0) {
                $status_msg = '未考勤';
            }
        }
        if ($item['type'] == 'QJ' && $item['reg_type'] == 0) {
            $status_msg = '已请假';
        }
        return $status_msg;
    }

    /**
     * 约课记录导出表格
     */
    public function export_table(){
        // 学员姓名及昵称
        $babyname = I('request.babyname', '', 'trim');
        if (!empty($babyname)) {
            $bid = $this->businessBabyinfo->alias('babyinfo')
                ->join('wzj_home_business_mapcase mapcase ON babyinfo.bid = mapcase.bid')
                ->where(['babyinfo.babyname|mapcase.studentname' => ['like', '%' . $babyname . '%']])
                ->field('babyinfo.bid')
                ->select();
            if (empty($bid)) {
                $bid = null;
            }
            $bid = array_column($bid, 'bid');
            $where['bid'] = $bid;
        }
        // 家长姓名
        $username = I('request.username', '', 'trim');
        if (!empty($username)) {
            $parent_pid = $this->businessParentinfo->where([
                'username' => ['like', '%' . $username . '%']
            ])->getField('pid', true);
            $where['pid'] = $parent_pid;
        }
        // 家长手机
        $phone = I('request.phone', '', 'trim');
        if (!empty($phone)) {
            $parent_pid = $this->businessParentinfo->where([
                'phone' => $phone
            ])->getField('pid', true);
            $where['pid'] = $parent_pid;
        }
        // 课程名称
        // 课程名称
        $stage_id = I('request.stage_id', '', 'trim');
        if (!empty($stage_id)) {
            $where['cs_id'] = $stage_id;
        }
        // 模糊搜索老师(授课，辅班，顾问)
        $teach_name = I('request.teach_name', '', 'trim');
        $fuban_name = I('request.fuban_name', '', 'trim');
        $sales_name = I('request.sales_name', '', 'trim');
        if (!empty($teach_name) || !empty($fuban_name) || !empty($sales_name)) {
            $uids = $this->member->where([
                'nickname' => ['like', '%' . $teach_name . $fuban_name . $sales_name . '%']
            ])->getField('uid', true);
            if (!empty($teach_name)) {
                $where['tc_id'] = $uids;
            }
            if (!empty($fuban_name)) {
                $where['zj_id'] = $uids;
            }
            if (!empty($sales_name)) {
                $where['sale_id'] = $uids;
            }
        }
        // 日期搜索 格式 2020-01-01至2020-01-02
        $ptdate = I('request.ptdate', '', 'trim');
        $now_date = date('Y-m') . '-01';
        $low_date = date('Y-m-d');
        if (!empty($ptdate)) {
            $ptdate_exp = explode('至', $ptdate);
            $now_date = $ptdate_exp[0];
            $low_date = $ptdate_exp[1];
        }
        $where['ptdate'] = [$now_date, $low_date];
        $type = I('request.type');
        if (!empty($type)) {
            $where['type'] = I('request.type');
        }
        $where['org_id'] = $this->org_id;
        $data = $this->jwStudentReg->yueke($where, 99999);
        $yueke = $data['yueke'];
        //表头数组
        $title = '约课记录';
        $expCellName[][1] = '学员姓名';
        $expCellName[][1] = '学员昵称';
        $expCellName[][1] = '联系电话';
        $expCellName[][1] = '课程名称';
        $expCellName[][1] = '上课日期';
        $expCellName[][1] = '上课时间';
        $expCellName[][1] = '授课老师';
        $expCellName[][1] = '辅班老师';
        $expCellName[][1] = '考勤状态';
        $expCellName[][1] = '备注';
        $expCellName[][1] = '影响课时';
        $expCellName[][1] = '使用课包';
        $expCellName[][1] = '老师评价';
        $expCellName[][1] = '家长回评';
        //导出数据
        $expTitle = $title.'_'.date('YmdHis');
        $xlsTitle = $expTitle;
        $fileName = $xlsTitle;
        $cellNum = count($expCellName);
        import('Org.Util.PHPExcelVendor');
        $objPHPExcel = new \PHPExcel();
        $cellName    = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '导出人:'.session('user_auth')['nickname'].' 导出时间：'.date('Y-m-d H:i'));
        for($i = 0; $i < $cellNum; $i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
        }
        if($yueke){
            $member = $this->member->getNicknameByOrgid($this->org_id);
            foreach ($yueke as $key => $val){
                // 是否课评
                $is_keping  = M('jw_eval')->where([
                    'org_id' => $this->org_id,
                    'reg_id' => $val['id']
                ])->find();
                // 状态
                $attan_status = $this->status_msg($val);
                // 课时
                if($attan_status == '已请假'){
                    $hour_str = 0;
                }elseif($attan_status == '补课'){
                    $hour_str = (-1 * $val['hour_bu']) . '补课';
                }else{
                    $hour_str = '';
                    $comma = '';
                    if($val['hour'] != 0){
                        $comma = ',';
                        $hour_str .= (-1 * $val['hour']) . '正课';
                    }
                    if($val['hour_zeng'] != 0){
                        $hour_str .= $comma . (-1 * $val['hour_zeng']) . '赠课';
                    }
                }
                // 课程名称
                $stage_title = $this->jwCourseStage->where([
                    'id' => $val['cs_id']
                ])->getField('title');
                // 备注
                $sign = D("JwSign")->where([
                    'ru_id' => $val['ru_id'],
                    'bid' => $val['bid'],
                    'share_bid' => $val['share_bid'],
                    'org_id' => $this->org_id,
                    'cont_num' => $val['cont_num'],
                    'type_status' => $val['type'],
                    'reg_type' => $val['reg_type'],
                    'sign_type' => $val['sign_type']
                ])->field('tips')->order('id desc')->limit(1)->find();
                $tips = '';
                if(!empty($sign)){
                    $tips = $sign['tips'];
                }
                $colunm_idx = 0;
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),$val['studentname']);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),$val['babyname']);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),$val['phone']);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),$stage_title);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),$val['ptdate']);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),$val['start_time'].'-'.$val['end_time']);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),$member[$val['tc_id']]);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),$member[$val['zj_id']]);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),$attan_status);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),$tips);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),$hour_str);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),$val['title']);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),empty($is_keping) ? '未课评' : '已课评');
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$colunm_idx++].($key+3),empty($val['iscomment']) ? '未评价' : '已评价');
            }
        }
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        ob_end_clean();
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        header("Expires:0");
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}
=======
>>>>>>> ce3
