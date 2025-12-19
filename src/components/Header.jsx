import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useRoot } from "../context/RootContext.jsx";
import './Header.css';


function Header(props) {
  const { user, authLoading, openModal } = useRoot();
  return (
	<>
		<div id='header'>
			<div id='header-contents'>
				<Link to='/' id='toback'>
					<span className="logo"><img src="/sky/sky.png" id='logo' /></span>
					<span id='pagename'>Sky</span>
				</Link>
				<div id='l'>
				{props.nonbut ? (
						<div></div>
						) : authLoading ? (<div className='spinner'></div>) : user ? (<Link className='lau' to="/profile">{user}</Link>) : (<Link className='lau' to="/register">Регистрация / Вход</Link>)
				}
				</div>
			</div>
		</div>
	</>
  );
}

export default Header;