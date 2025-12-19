import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import './index.css';
import App from './App.jsx';
import { BrowserRouter as Router } from 'react-router-dom';

import { RootProvider } from './context/RootContext.jsx';
import Modal from './components/Modal.jsx';

createRoot(document.getElementById('root')).render(
  <StrictMode>
      <Router basename="/sky">
		<RootProvider>
			<App />
			<Modal />
		</RootProvider>		
      </Router>
  </StrictMode>
);
