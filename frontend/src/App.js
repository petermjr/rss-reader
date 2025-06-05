import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { Container } from 'react-bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import './App.css';

// Components
import Navbar from './components/Navbar';
import FeedList from './components/FeedList';
import FeedDetail from './components/FeedDetail';
import AddFeed from './components/AddFeed';

function App() {
  return (
    <Router>
      <div className="App">
        <Navbar />
        <Container className="mt-4">
          <Routes>
            <Route path="/" element={<FeedList />} />
            <Route path="/feed/:id" element={<FeedDetail />} />
            <Route path="/add" element={<AddFeed />} />
          </Routes>
        </Container>
      </div>
    </Router>
  );
}

export default App; 