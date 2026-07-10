@extends('errors.layout')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('heading', __('Unrecognized Identity'))
@section('description', __('You must reveal your true identity before entering this realm. Please authenticate yourself to proceed.'))
