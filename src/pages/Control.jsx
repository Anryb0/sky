import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Header from '../components/Header.jsx'
import './Control.css';
import { useRoot } from '../context/RootContext.jsx';

function Control(){
	const navigate = useNavigate();
	const { user, authLoading, openModal } = useRoot();
	const[loading,setLoading] = useState(true);
	
	if(!authLoading && !user){
		navigate('/');
	}
	
	useEffect(()=>{
		const queryString = window.location.search;
		const urlParams = new URLSearchParams(queryString);
	},[])
	
	return (
		<>
			<Header />
			<main>
				
			</main>
		</>
	)
}

export default Control;