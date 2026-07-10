@extends('errors.layout')

@section('title', __('Too Many Requests'))
@section('code', '429')
@section('heading', __('The Flow is Overwhelmed'))
@section('description', __('You are spinning the threads too quickly. The magical currents need a moment to settle before you can cast another request.'))
