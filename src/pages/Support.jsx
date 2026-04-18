import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useRoot } from "../context/RootContext.jsx";
import Header from '../components/Header.jsx';
import './Support.css';


function Support(){
	return (
		<>
			<Header />
			<main>
				<h2>Информация</h2>
				<hr />
				<p>Проект создан исключительно в образовательных целях. Предоставление услуг за денежные средства не производится. Платежная система подключена в тестовом режиме.</p>
				<p>Связаться с автором: <u><Link to="https://t.me/kekz663">telegram</Link></u> </p>
			</main>
		</>
	)
}

export default Support;