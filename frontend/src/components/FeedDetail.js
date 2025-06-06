import React, { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Card, Button, ListGroup, Alert } from 'react-bootstrap';
import axios from 'axios';

const FeedDetail = () => {
  const { id } = useParams();
  const [feed, setFeed] = useState(null);
  const [entries, setEntries] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [refreshing, setRefreshing] = useState(false);

  const fetchFeedDetails = useCallback(async () => {
    try {
      const response = await axios.get(`/api/feeds/${id}`);
      setFeed(response.data.feed);
      setEntries(response.data.feed.entries || []);
      setError(null);
    } catch (err) {
      setError('Failed to load feed details');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    fetchFeedDetails();
  }, [fetchFeedDetails]);

  const handleRefresh = async () => {
    setRefreshing(true);
    try {
      await axios.post(`/api/feeds/${id}/refresh`);
      await fetchFeedDetails();
    } catch (err) {
      setError('Failed to refresh feed');
    } finally {
      setRefreshing(false);
    }
  };

  if (loading) {
    return <div className="text-center p-4">Loading...</div>;
  }

  if (error) {
    return <div className="text-center text-red-500 p-4">{error}</div>;
  }

  if (!feed) {
    return <div className="text-center p-4">Feed not found</div>;
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold">{feed.title}</h1>
        <button
          onClick={handleRefresh}
          disabled={refreshing}
          className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
        >
          {refreshing ? 'Refreshing...' : 'Refresh Feed'}
        </button>
      </div>

      {feed.description && (
        <p className="text-gray-600 mb-6">{feed.description}</p>
      )}

      <div className="bg-white rounded-lg shadow-md">
        {feed.entries.map(entry => (
          <div key={entry.id} className="border-b border-gray-200 last:border-b-0">
            <a
              href={entry.link}
              target="_blank"
              rel="noopener noreferrer"
              className="block p-6 hover:bg-gray-50 transition-colors duration-150"
            >
              <h2 className="text-xl font-semibold text-gray-900 mb-2">
                {entry.title}
              </h2>
              {entry.description && (
                <p className="text-gray-600 mb-2">{entry.description}</p>
              )}
              <p className="text-sm text-gray-500">
                {new Date(entry.published_at).toLocaleDateString()}
              </p>
            </a>
          </div>
        ))}
        {feed.entries.length === 0 && (
          <div className="text-center p-6 text-gray-500">
            No entries found
          </div>
        )}
      </div>
    </div>
  );
};

export default FeedDetail; 