@extends('errors.layout')

@section('title', 'Too Many Requests')
@section('code', '429')
@section('heading', 'The Flow is Overwhelmed')
@section('description', 'You are spinning the threads too quickly. The magical currents need a moment to settle before you can cast another request.')
