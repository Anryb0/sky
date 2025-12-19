import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../components/Header.jsx';
import { useRoot } from "../context/RootContext.jsx";
import './Start.css';

function Start() {
  const { user, authLoading } = useRoot();
  const navigate = useNavigate();
  const [loading,setLoading] = useState(true);
  useEffect(() => {
    if (!authLoading && !user) {
      navigate('/register');
    }
  }, [authLoading, user, navigate]);
  useEffect(() => {
    if (!authLoading && user) {
		let xhr = new XMLHttpRequest();
		
	}
  }, [authLoading, user]);
  if (authLoading || !user || loading ) {
    return (
      <>
        <Header />
        {(authLoading || loading) && <div className='spinner center'></div>}
      </>
    );
  }

  return (
    <>
      <Header />
      <main>
        <div className='glassy'>
			<h3>Какие доступны тарифы?</h3>
		</div>
      </main>
    </>
  );
}

export default Start;
