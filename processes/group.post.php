<?php
/**
 * 이 파일은 아이모듈 관리자모듈의 일부입니다. (https://www.imodules.io)
 *
 * 그룹정보를 저장한다.
 *
 * @file /modules/member/processes/group.post.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 1. 26.
 *
 * @var \modules\member\Member $me
 */
if (defined('__IM_PROCESS__') == false) {
    exit();
}

/**
 * 관리자권한이 존재하는지 확인한다.
 */
if ($me->getAdmin()->checkPermission('members', ['groups']) == false) {
    $results->success = false;
    $results->message = $me->getErrorText('FORBIDDEN');
    return;
}

$group_id = Request::get('group_id');
if ($group_id !== null) {
    $group = $me
        ->db()
        ->select()
        ->from($me->table('groups'))
        ->where('group_id', $group_id)
        ->getOne();

    if ($group === null) {
        $results->success = false;
        $results->message = $me->getErrorText('NOT_FOUND_DATA');
        return;
    }
} else {
    $group = null;
}

$errors = [];
$group_id = Input::get('group_id');
$parent_id = Input::get('parent_id') ?? 'all';
$title = Input::get('title', $errors);
if ($title !== null) {
    $checked = $me
        ->db()
        ->select()
        ->from($me->table('groups'))
        ->where('title', $title);
    if ($group !== null) {
        $checked->where('group_id', $group->group_id, '!=');
    }
    if ($checked->has() == true) {
        $errors['title'] = $me->getErrorText('DUPLICATED');
    }
}

if (count($errors) == 0) {
    $parent = $parent == 'all' ? null : $parent;
    if ($group !== null && $group->parent != $parent) {
        // @todo 상위그룹이 변경되었을 경우
    }

    $group_id ??= UUID::v1($title);
    $me->db()
        ->insert(
            $me->table('groups'),
            ['group_id' => $group_id, 'parent_id' => $parent_id, 'title' => $title],
            ['parent_id', 'title']
        )
        ->execute();

    /**
     * @var \modules\member\admin\Member $mAdmin
     */
    $mAdmin->updateGroup($group_id);

    $results->success = true;
    $results->group_id = $group_id;
} else {
    $results->success = false;
    $results->errors = $errors;
}
