
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
